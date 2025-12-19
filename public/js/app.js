// Global, non-bundled frontend JS for the public site.
// Loaded from templates/base.html.twig (deferred).

(function () {
    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn, { once: true });
            return;
        }

        fn();
    }

    // ---------------------------------------------------------------------
    // Mobile menu (no inline onclick)
    // ---------------------------------------------------------------------
    onReady(function () {
        const button = document.getElementById('angoMobileMenuButton');
        const menu = document.getElementById('mobile-menu');
        if (!button || !menu) return;

        button.addEventListener('click', function () {
            menu.classList.toggle('hidden');
            const expanded = !menu.classList.contains('hidden');
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    });

    // ---------------------------------------------------------------------
    // Booking modal (exposes same globals as old inline script)
    // ---------------------------------------------------------------------
    (function () {
        // --- UI LOGIC ---
        const CATEGORY_LABELS = {
            hair: 'Վարսահարդարում',
            makeup: 'Դիմահարդարում',
            nails: 'Մատնահարդարում',
        };
        const CATEGORY_ORDER = { hair: 1, makeup: 2, nails: 3 };

        if (typeof window.__bookingMode === 'undefined') {
            window.__bookingMode = 'global'; // 'global' | 'artist'
        }
        if (typeof window.__bookingArtistsCache === 'undefined') {
            window.__bookingArtistsCache = null; // cached from /api/booking/artists
        }
        if (typeof window.__bookingServicesCache === 'undefined') {
            window.__bookingServicesCache = null; // cached from /api/booking/services
        }

        /** @type {Array<{id:number|string,name:string,category?:string,price?:number,durationMinutes?:number}>} */
        let currentServices = [];
        /** @type {string|null} */
        let currentArtistId = null;

        function openBooking(options = {}) {
            const modal = document.getElementById('bookingModal');
            const content = document.getElementById('bookingContent');
            if (!modal || !content) return;

            modal.classList.remove('hidden');
            // Small delay for animation
            setTimeout(() => {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);

            const artistId = options && options.artistId ? String(options.artistId) : null;
            window.__bookingMode = artistId ? 'artist' : 'global';
            currentArtistId = artistId;
            currentServices = [];

            resetBookingFormState();

            if (artistId) {
                // Artist-specific booking: artist is locked, services are filtered by artist
                setArtistLocked(artistId);
                loadServicesForArtist(artistId).then((services) => {
                    currentServices = services;
                    populateCategoryOptionsFromServices(services);
                    autoSelectCategoryIfSingle(services);
                });
            } else {
                // Warm up global services cache (doesn't block UI)
                ensureServicesCache().then((services) => {
                    currentServices = services;
                });
            }
        }

        function closeBooking() {
            const modal = document.getElementById('bookingModal');
            const content = document.getElementById('bookingContent');
            if (!modal || !content) return;

            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        // --- API LOGIC ---
        function resetBookingFormState() {
            const categorySelect = document.getElementById('categorySelect');
            const artistSelect = document.getElementById('artistSelect');
            const serviceSelect = document.getElementById('serviceSelect');
            const dateInput = document.getElementById('dateInput');

            if (categorySelect) {
                const keys = Object.keys(CATEGORY_LABELS).sort((a, b) => (CATEGORY_ORDER[a] ?? 99) - (CATEGORY_ORDER[b] ?? 99));
                categorySelect.innerHTML = '<option value="">-- Ընտրել --</option>' + keys.map(k => `<option value="${k}">${CATEGORY_LABELS[k] || k}</option>`).join('');
                categorySelect.value = '';
                categorySelect.disabled = false;
            }

            if (serviceSelect) {
                serviceSelect.innerHTML = '<option value="">-- Նախ ընտրեք կատեգորիան --</option>';
                serviceSelect.value = '';
            }

            if (artistSelect) {
                artistSelect.disabled = false;
                artistSelect.value = '';
                artistSelect.innerHTML = '<option value="">-- Նախ ընտրեք ծառայությունը --</option>';
            }

            if (dateInput) dateInput.value = '';

            // steps
            document.getElementById('stepService')?.classList.add('opacity-50', 'pointer-events-none');
            document.getElementById('stepArtist')?.classList.add('opacity-50', 'pointer-events-none');
            document.getElementById('stepDate')?.classList.add('opacity-50', 'pointer-events-none');
            document.getElementById('stepSlots')?.classList.add('hidden');
            document.getElementById('stepClient')?.classList.add('hidden');
            const selectedTime = document.getElementById('selectedTime');
            if (selectedTime) selectedTime.value = '';
        }

        async function ensureArtistsCache() {
            if (window.__bookingArtistsCache) return window.__bookingArtistsCache;
            const response = await fetch('/api/booking/artists');
            const artists = await response.json();
            window.__bookingArtistsCache = Array.isArray(artists) ? artists : [];
            return window.__bookingArtistsCache;
        }

        async function setArtistLocked(artistId) {
            const artistSelect = document.getElementById('artistSelect');
            const stepArtist = document.getElementById('stepArtist');
            if (!artistSelect || !stepArtist) return;

            artistSelect.disabled = true;
            artistSelect.innerHTML = `<option value="${artistId}">Բեռնվում է...</option>`;
            artistSelect.value = artistId;
            stepArtist.classList.remove('opacity-50', 'pointer-events-none');

            try {
                const artists = await ensureArtistsCache();
                const found = artists.find(a => String(a.id) === String(artistId));
                artistSelect.innerHTML = `<option value="${artistId}">${found ? found.name : ('Artist #' + artistId)}</option>`;
                artistSelect.value = artistId;
            } catch (e) {
                artistSelect.innerHTML = `<option value="${artistId}">Artist #${artistId}</option>`;
                artistSelect.value = artistId;
            }
        }

        async function loadServicesForArtist(artistId) {
            try {
                const response = await fetch(`/api/booking/services/${artistId}`);
                const services = await response.json();
                return Array.isArray(services) ? services : [];
            } catch (e) {
                console.error('Error:', e);
                return [];
            }
        }

        function populateCategoryOptionsFromServices(services) {
            const categorySelect = document.getElementById('categorySelect');
            if (!categorySelect) return;

            const categories = Array.from(new Set(
                (Array.isArray(services) ? services : [])
                    .map(s => (s && s.category ? String(s.category) : ''))
                    .filter(Boolean)
            ));

            if (categories.length === 0) return;

            categories.sort((a, b) => (CATEGORY_ORDER[a] ?? 99) - (CATEGORY_ORDER[b] ?? 99));

            const current = categorySelect.value;
            categorySelect.innerHTML = '<option value="">-- Ընտրել --</option>' + categories.map(k => `<option value="${k}">${CATEGORY_LABELS[k] || k}</option>`).join('');
            const next = categories.includes(current) ? current : '';
            categorySelect.value = next;
            if (next !== current) {
                onCategoryChange();
            }
        }

        function autoSelectCategoryIfSingle(services) {
            const categorySelect = document.getElementById('categorySelect');
            if (!categorySelect) return;

            const categories = Array.from(new Set(
                (Array.isArray(services) ? services : [])
                    .map(s => (s && s.category ? String(s.category) : ''))
                    .filter(Boolean)
            ));

            if (categories.length !== 1) return;

            categorySelect.value = categories[0];
            onCategoryChange();
        }

        function readServicesFromDomFallback() {
            const serviceSelect = document.getElementById('serviceSelect');
            if (!serviceSelect) return [];

            const services = [];
            Array.from(serviceSelect.options).forEach((opt) => {
                if (!opt.value) return;

                const category = opt.dataset.category ? String(opt.dataset.category) : '';
                if (!category) return;

                const rawLabel = (opt.textContent || '').trim();
                const name = opt.dataset.name ? String(opt.dataset.name) : rawLabel.replace(/\s*\(\s*[\d.]+\s*AMD\s*\)\s*$/, '').trim();
                let price = undefined;
                if (opt.dataset.price) {
                    const p = Number(opt.dataset.price);
                    price = Number.isFinite(p) ? p : undefined;
                } else {
                    const m = rawLabel.match(/\(\s*([\d.]+)\s*AMD\s*\)/);
                    if (m && m[1]) {
                        const p = Number(m[1]);
                        price = Number.isFinite(p) ? p : undefined;
                    }
                }

                services.push({ id: opt.value, name, category, price });
            });

            return services;
        }

        async function ensureServicesCache() {
            if (window.__bookingServicesCache !== null) return window.__bookingServicesCache;

            try {
                const response = await fetch('/api/booking/services');
                const services = await response.json();
                window.__bookingServicesCache = Array.isArray(services) ? services : [];
            } catch (e) {
                console.error('Error:', e);
                window.__bookingServicesCache = readServicesFromDomFallback();
            }

            return window.__bookingServicesCache;
        }

        async function ensureCurrentServices() {
            if (Array.isArray(currentServices) && currentServices.length) return currentServices;

            if (window.__bookingMode === 'artist' && currentArtistId) {
                currentServices = await loadServicesForArtist(currentArtistId);
                populateCategoryOptionsFromServices(currentServices);
                return currentServices;
            }

            currentServices = await ensureServicesCache();
            return currentServices;
        }

        async function onCategoryChange() {
            const category = document.getElementById('categorySelect')?.value;
            const serviceSelect = document.getElementById('serviceSelect');
            const artistSelect = document.getElementById('artistSelect');
            const dateInput = document.getElementById('dateInput');

            // reset downstream
            document.getElementById('stepSlots')?.classList.add('hidden');
            document.getElementById('stepClient')?.classList.add('hidden');
            document.getElementById('stepDate')?.classList.add('opacity-50', 'pointer-events-none');
            if (dateInput) dateInput.value = '';

            const selectedTime = document.getElementById('selectedTime');
            if (selectedTime) selectedTime.value = '';

            if (window.__bookingMode === 'global') {
                document.getElementById('stepArtist')?.classList.add('opacity-50', 'pointer-events-none');
                if (artistSelect) {
                    artistSelect.disabled = false;
                    artistSelect.value = '';
                    artistSelect.innerHTML = '<option value="">-- Նախ ընտրեք ծառայությունը --</option>';
                }
            }

            if (!category) {
                document.getElementById('stepService')?.classList.add('opacity-50', 'pointer-events-none');
                if (serviceSelect) {
                    serviceSelect.innerHTML = '<option value="">-- Նախ ընտրեք կատեգորիան --</option>';
                    serviceSelect.value = '';
                }
                return;
            }

            document.getElementById('stepService')?.classList.remove('opacity-50', 'pointer-events-none');
            if (!serviceSelect) return;

            serviceSelect.innerHTML = '<option value="">Բեռնվում է...</option>';
            serviceSelect.value = '';

            const services = await ensureCurrentServices();
            const filtered = (Array.isArray(services) ? services : [])
                .filter(s => String(s.category || '') === String(category))
                .sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'hy'));

            if (filtered.length === 0) {
                serviceSelect.innerHTML = '<option value="">Այս կատեգորիայում ծառայություններ չկան</option>';
                return;
            }

            serviceSelect.innerHTML = '<option value="">-- Ընտրել --</option>';
            filtered.forEach((service) => {
                const name = String(service.name || '').trim();
                const price = service.price;
                const label = name + ((price !== undefined && price !== null && price !== '') ? ` (${price} AMD)` : '');
                serviceSelect.innerHTML += `<option value="${service.id}">${label}</option>`;
            });

            // If only one service, auto-select it
            if (filtered.length === 1) {
                serviceSelect.value = String(filtered[0].id);
                onServiceChange();
            }
        }

        async function loadArtistsByService(serviceId) {
            const artistSelect = document.getElementById('artistSelect');
            const stepArtist = document.getElementById('stepArtist');
            if (!artistSelect || !stepArtist) return;

            artistSelect.disabled = false;
            artistSelect.innerHTML = '<option value="">Բեռնվում է...</option>';
            artistSelect.value = '';

            try {
                const response = await fetch(`/api/booking/artists/${serviceId}`);
                const artists = await response.json();
                artistSelect.innerHTML = '<option value="">-- Ընտրել --</option>';
                (Array.isArray(artists) ? artists : []).forEach(artist => {
                    artistSelect.innerHTML += `<option value="${artist.id}">${artist.name}</option>`;
                });
                stepArtist.classList.remove('opacity-50', 'pointer-events-none');
            } catch (e) {
                console.error('Error:', e);
                artistSelect.innerHTML = '<option value="">Չհաջողվեց բեռնել վարպետներին</option>';
            }
        }

        function onServiceChange() {
            const serviceId = document.getElementById('serviceSelect')?.value;
            const dateInput = document.getElementById('dateInput');

            // reset downstream
            document.getElementById('stepDate')?.classList.add('opacity-50', 'pointer-events-none');
            document.getElementById('stepSlots')?.classList.add('hidden');
            document.getElementById('stepClient')?.classList.add('hidden');
            const selectedTime = document.getElementById('selectedTime');
            if (selectedTime) selectedTime.value = '';
            if (dateInput) dateInput.value = '';

            if (window.__bookingMode === 'global') {
                if (!serviceId) {
                    document.getElementById('stepArtist')?.classList.add('opacity-50', 'pointer-events-none');
                    const artistSelect = document.getElementById('artistSelect');
                    if (artistSelect) {
                        artistSelect.disabled = false;
                        artistSelect.value = '';
                        artistSelect.innerHTML = '<option value="">-- Նախ ընտրեք ծառայությունը --</option>';
                    }
                    return;
                }

                loadArtistsByService(serviceId);
                return;
            }

            // artist mode: artist already selected, just enable date when both chosen
            enableDate();
        }

        function onArtistChange() {
            // reset downstream
            document.getElementById('stepSlots')?.classList.add('hidden');
            document.getElementById('stepClient')?.classList.add('hidden');
            const selectedTime = document.getElementById('selectedTime');
            if (selectedTime) selectedTime.value = '';

            enableDate();
        }

        function enableDate() {
            const serviceId = document.getElementById('serviceSelect')?.value;
            const artistId = document.getElementById('artistSelect')?.value;
            const stepDate = document.getElementById('stepDate');

            if (serviceId && artistId) {
                stepDate?.classList.remove('opacity-50', 'pointer-events-none');
            } else {
                stepDate?.classList.add('opacity-50', 'pointer-events-none');
            }
        }

        async function loadSlots() {
            const artistSelect = document.getElementById('artistSelect');
            const serviceSelect = document.getElementById('serviceSelect');
            const dateInput = document.getElementById('dateInput');
            const slotsContainer = document.getElementById('slotsContainer');
            const stepSlots = document.getElementById('stepSlots');

            if (!artistSelect || !serviceSelect || !dateInput || !slotsContainer || !stepSlots) return;

            const artistId = artistSelect.value;
            const serviceId = serviceSelect.value;
            const date = dateInput.value;

            if (!artistId || !serviceId || !date) return;

            slotsContainer.innerHTML = '<p class="col-span-4 text-center text-gray-500">Բեռնվում է...</p>';
            stepSlots.classList.remove('hidden');

            try {
                const url = `/api/booking/slots?artistId=${artistId}&serviceId=${serviceId}&date=${date}`;
                const response = await fetch(url);
                const data = await response.json();

                slotsContainer.innerHTML = '';

                if (!data || !Array.isArray(data.slots) || data.slots.length === 0) {
                    slotsContainer.innerHTML = '<p class="col-span-4 text-center text-red-500">Այս օրը ազատ տեղեր չկան:</p>';
                    return;
                }

                data.slots.forEach(time => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'border border-pink-200 text-pink-600 hover:bg-pink-600 hover:text-white py-2 rounded transition text-sm font-bold';
                    btn.innerText = time;
                    btn.onclick = () => selectTime(btn, time);
                    slotsContainer.appendChild(btn);
                });
            } catch (e) {
                console.error(e);
            }
        }

        function selectTime(btn, time) {
            // Remove active class from others
            document.querySelectorAll('#slotsContainer button').forEach(b => {
                b.classList.remove('bg-pink-600', 'text-white');
                b.classList.add('text-pink-600');
            });

            // Add active class
            btn.classList.remove('text-pink-600');
            btn.classList.add('bg-pink-600', 'text-white');

            const selectedTime = document.getElementById('selectedTime');
            if (selectedTime) selectedTime.value = time;
            document.getElementById('stepClient')?.classList.remove('hidden');
        }

        async function submitBooking(e) {
            if (e && typeof e.preventDefault === 'function') {
                e.preventDefault();
            }

            const data = {
                serviceId: document.getElementById('serviceSelect')?.value,
                artistId: document.getElementById('artistSelect')?.value,
                date: document.getElementById('dateInput')?.value,
                time: document.getElementById('selectedTime')?.value,
                name: document.getElementById('clientName')?.value,
                phone: document.getElementById('clientPhone')?.value,
                email: document.getElementById('clientEmail')?.value
            };

            try {
                const response = await fetch('/api/booking/book', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result && result.success) {
                    alert('Շնորհավորում ենք! Ձեր ամրագրումը կատարված է: Շուտով կապ կհաստատենք:');
                    location.reload();
                } else {
                    alert('Սխալ: ' + JSON.stringify(result));
                }
            } catch (err) {
                alert('Համակարգային սխալ');
            }
        }

        // Wire modal UI events (keeps Twig templates free from inline JS handlers).
        onReady(function () {
            const closeButton = document.getElementById('bookingCloseButton');
            if (closeButton) closeButton.addEventListener('click', closeBooking);

            const form = document.getElementById('bookingForm');
            if (form) form.addEventListener('submit', submitBooking);

            const categorySelect = document.getElementById('categorySelect');
            if (categorySelect) categorySelect.addEventListener('change', onCategoryChange);

            const serviceSelect = document.getElementById('serviceSelect');
            if (serviceSelect) serviceSelect.addEventListener('change', onServiceChange);

            const artistSelect = document.getElementById('artistSelect');
            if (artistSelect) artistSelect.addEventListener('change', onArtistChange);

            const dateInput = document.getElementById('dateInput');
            if (dateInput) dateInput.addEventListener('change', loadSlots);
        });

        // Expose globals (keeps existing inline handlers working).
        window.openBooking = openBooking;
        window.closeBooking = closeBooking;
        window.onCategoryChange = onCategoryChange;
        window.onServiceChange = onServiceChange;
        window.onArtistChange = onArtistChange;
        window.loadSlots = loadSlots;
        window.submitBooking = submitBooking;

        // Attach a single delegated handler for all booking triggers (works after AJAX swaps too).
        document.addEventListener('click', function (e) {
            const a = e.target.closest('a[href="#booking"]');
            if (!a) return;

            e.preventDefault();

            if (typeof window.openBooking !== 'function') {
                console.error('Booking modal function not found.');
                return;
            }

            const artistId = a.dataset.artistId ? String(a.dataset.artistId) : null;
            if (artistId) {
                window.openBooking({ artistId });
            } else {
                window.openBooking();
            }
        });
    })();

    // ---------------------------------------------------------------------
    // Snowfall overlay
    // ---------------------------------------------------------------------
    (function () {
        function initSnow() {
            const canvas = document.getElementById('angoSnowCanvas');
            if (!canvas) return;

            // Respect accessibility & performance preferences.
            const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const saveData = !!(navigator.connection && navigator.connection.saveData);
            if (prefersReducedMotion || saveData) {
                canvas.style.display = 'none';
                return;
            }

            /** @type {CanvasRenderingContext2D|null} */
            const ctx = canvas.getContext('2d', { alpha: true });
            if (!ctx) return;

            let width = 0;
            let height = 0;
            let dpr = 1;
            /** @type {Array<{x:number,y:number,r:number,vy:number,vx:number,wobble:number,wobbleSpeed:number,o:number}>} */
            let flakes = [];
            let rafId = 0;
            let lastTs = performance.now();

            function rand(min, max) {
                return Math.random() * (max - min) + min;
            }

            function resizeCanvas() {
                // Use bounding box to match CSS size (handles zoom/layout).
                const rect = canvas.getBoundingClientRect();
                width = Math.max(1, Math.floor(rect.width));
                height = Math.max(1, Math.floor(rect.height));
                dpr = Math.min(2, window.devicePixelRatio || 1);

                canvas.width = Math.floor(width * dpr);
                canvas.height = Math.floor(height * dpr);
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            }

            function makeFlake() {
                const r = rand(0.8, 2.8);
                return {
                    x: rand(0, width),
                    y: rand(-height, height),
                    r,
                    vy: rand(0.7, 1.6),
                    vx: rand(-0.25, 0.25),
                    wobble: rand(0, Math.PI * 2),
                    wobbleSpeed: rand(0.008, 0.02),
                    o: rand(0.25, 0.75)
                };
            }

            function initFlakes() {
                // Density scales with viewport, with a safe upper bound.
                const target = Math.min(180, Math.max(60, Math.round(width / 10)));
                flakes = new Array(target).fill(0).map(makeFlake);
            }

            function step(ts) {
                const dt = Math.min(0.05, (ts - lastTs) / 1000); // cap to avoid huge jumps
                lastTs = ts;

                ctx.clearRect(0, 0, width, height);

                for (let i = 0; i < flakes.length; i++) {
                    const f = flakes[i];
                    f.wobble += f.wobbleSpeed;

                    // 60 is a normalization factor so speeds feel consistent across dt.
                    const dx = (f.vx + Math.sin(f.wobble) * 0.25) * 60 * dt;
                    const dy = f.vy * 60 * dt;
                    f.x += dx;
                    f.y += dy;

                    // Wrap around screen bounds.
                    if (f.y - f.r > height) {
                        f.y = -f.r;
                        f.x = rand(0, width);
                    }
                    if (f.x < -f.r) f.x = width + f.r;
                    if (f.x > width + f.r) f.x = -f.r;

                    ctx.globalAlpha = f.o;
                    ctx.beginPath();
                    ctx.arc(f.x, f.y, f.r, 0, Math.PI * 2);
                    ctx.fillStyle = '#ffffff';
                    ctx.fill();
                }

                ctx.globalAlpha = 1;
                rafId = requestAnimationFrame(step);
            }

            function start() {
                if (rafId) return;
                lastTs = performance.now();
                rafId = requestAnimationFrame(step);
            }

            function stop() {
                if (!rafId) return;
                cancelAnimationFrame(rafId);
                rafId = 0;
            }

            // Init
            resizeCanvas();
            initFlakes();
            start();

            // Re-init on resize/orientation changes.
            window.addEventListener('resize', function () {
                resizeCanvas();
                initFlakes();
            }, { passive: true });

            // Pause when tab is hidden to save CPU.
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) stop();
                else start();
            });
        }

        // Defer initialization to avoid impacting LCP/first render.
        const run = function () {
            try {
                initSnow();
            } catch (e) {
                // no-op
            }
        };

        if (typeof window.requestIdleCallback === 'function') {
            window.requestIdleCallback(run, { timeout: 2000 });
        } else {
            setTimeout(run, 1200);
        }
    })();

    // ---------------------------------------------------------------------
    // AJAX filter navigation (no full reload)
    // ---------------------------------------------------------------------
    (function () {
        let lastSwapId = 0;

        function canHandleClick(e, a) {
            if (e.defaultPrevented) return false;
            if (typeof e.button === 'number' && e.button !== 0) return false;
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return false;
            if (a.target && a.target !== '_self') return false;
            if (a.hasAttribute('download')) return false;
            const href = a.getAttribute('href');
            if (!href || href.startsWith('#')) return false;
            return true;
        }

        async function angoSwap(url, selector, push) {
            const swapId = ++lastSwapId;
            const target = document.querySelector(selector);
            if (!target) {
                window.location.href = url;
                return;
            }

            target.setAttribute('aria-busy', 'true');
            target.classList.add('opacity-50', 'pointer-events-none');

            try {
                const resp = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    },
                    credentials: 'same-origin'
                });

                if (!resp.ok) throw new Error('Bad response');
                const html = await resp.text();

                // Ignore out-of-order responses if the user clicked multiple filters quickly
                if (swapId !== lastSwapId) return;

                const doc = new DOMParser().parseFromString(html, 'text/html');
                const next = doc.querySelector(selector);
                if (!next) {
                    window.location.href = url;
                    return;
                }

                target.replaceWith(next);
                if (doc.title) document.title = doc.title;

                if (push) {
                    history.pushState({ __angoAjaxSwap: true, selector }, '', url);
                }
            } catch (err) {
                window.location.href = url;
            } finally {
                const t = document.querySelector(selector);
                if (t) {
                    t.removeAttribute('aria-busy');
                    t.classList.remove('opacity-50', 'pointer-events-none');
                }
            }
        }

        document.addEventListener('click', function (e) {
            const a = e.target.closest('a[data-ajax-swap]');
            if (!a) return;
            if (!canHandleClick(e, a)) return;

            const selector = a.dataset.ajaxSwap;
            const href = a.getAttribute('href');
            if (!selector || !href) return;

            const url = new URL(href, window.location.href);
            if (url.origin !== window.location.origin) return;

            e.preventDefault();

            // Mark current page state so browser Back restores the correct filtered content
            const currentState = history.state || {};
            if (!currentState.__angoAjaxSwap || currentState.selector !== selector) {
                history.replaceState({ ...currentState, __angoAjaxSwap: true, selector }, '', window.location.href);
            }

            angoSwap(url.toString(), selector, true);
        }, true);

        window.addEventListener('popstate', function (e) {
            const state = e.state;
            if (!state || !state.__angoAjaxSwap || !state.selector) return;
            angoSwap(window.location.href, state.selector, false);
        });
    })();
})();


