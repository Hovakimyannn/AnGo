// Blog post page interactions (rating + comments).
// Loaded from templates/blog/show.html.twig (deferred).

(function () {
    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn, { once: true });
            return;
        }

        fn();
    }

    onReady(function () {
        const configEl = document.getElementById('angoBlogShowConfig');
        const loginUrl = configEl ? String(configEl.dataset.loginUrl || '') : '';

        function setAjaxMessage(el, message, type) {
            if (!el) return;
            if (!message) {
                el.classList.add('hidden');
                el.textContent = '';
                return;
            }

            el.classList.remove('hidden');
            el.classList.remove('bg-red-50', 'border-red-200', 'text-red-800', 'bg-green-50', 'border-green-200', 'text-green-800');

            if (type === 'success') {
                el.classList.add('bg-green-50', 'border-green-200', 'text-green-800');
            } else {
                el.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
            }
            el.textContent = message;
        }

        function retrigger(el, className) {
            if (!el) return;
            el.classList.remove(className);
            // Force reflow so animation can replay
            void el.offsetWidth;
            el.classList.add(className);
        }

        async function postForm(url, params) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: new URLSearchParams(params)
            });

            let data = null;
            try {
                data = await response.json();
            } catch (e) {
                data = null;
            }

            return { response, data };
        }

        // --- Rating AJAX ---
        const ratingForm = document.getElementById('ratingForm');
        if (ratingForm) {
            const ratingMsg = document.getElementById('ratingAjaxMessage');
            const ratingAvgEl = document.getElementById('ratingAvg');
            const ratingCountEl = document.getElementById('ratingCount');
            const ratingFillEl = document.getElementById('ratingFill');
            const ratingPill = document.getElementById('ratingPill');
            const ratingSavedIcon = document.getElementById('ratingSavedIcon');

            // Initial fill from server-rendered percent (avoid inline styles in Twig).
            if (ratingFillEl) {
                const initial = Number(ratingFillEl.dataset.percent ?? 0);
                const percent = Math.max(0, Math.min(100, initial));
                ratingFillEl.style.width = percent.toFixed(2) + '%';
            }

            ratingForm.addEventListener('change', async (e) => {
                const target = e.target;
                if (!target || target.name !== 'value') return;

                setAjaxMessage(ratingMsg, '', 'success');

                const token = ratingForm.querySelector('input[name="_token"]')?.value || '';
                const value = String(target.value || '');
                if (!value) return;

                // Optimistic: disable inputs while saving
                const inputs = Array.from(ratingForm.querySelectorAll('input[name="value"]'));
                inputs.forEach(i => i.disabled = true);

                try {
                    const { response, data } = await postForm(ratingForm.action, { _token: token, value });

                    if (response.status === 401 || response.status === 403) {
                        if (loginUrl) window.location.href = loginUrl;
                        return;
                    }

                    if (!response.ok || !data || data.success !== true) {
                        setAjaxMessage(ratingMsg, (data && data.error) ? data.error : 'Չհաջողվեց պահպանել գնահատականը։', 'error');
                        return;
                    }

                    const avg = Number(data.avg ?? 0);
                    const count = Number(data.count ?? 0);

                    if (ratingAvgEl) ratingAvgEl.textContent = avg.toFixed(1);
                    if (ratingCountEl) ratingCountEl.textContent = '(' + String(count) + ')';
                    if (ratingFillEl) {
                        const percent = Math.max(0, Math.min(100, (avg / 5) * 100));
                        ratingFillEl.style.width = percent.toFixed(2) + '%';
                    }

                    // Success effect (no reload)
                    retrigger(ratingPill, 'ango-rate-pop');
                    retrigger(ratingSavedIcon, 'ango-rate-saved');

                    setTimeout(() => setAjaxMessage(ratingMsg, '', 'success'), 1500);
                } catch (err) {
                    setAjaxMessage(ratingMsg, 'Համակարգային սխալ։', 'error');
                } finally {
                    inputs.forEach(i => i.disabled = false);
                }
            });
        }

        // --- Comment AJAX ---
        const commentForm = document.getElementById('commentForm');
        if (commentForm) {
            const commentMsg = document.getElementById('commentAjaxMessage');
            const commentBody = document.getElementById('commentBody');
            const commentSubmit = document.getElementById('commentSubmit');
            const commentsList = document.getElementById('commentsList');

            commentForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                setAjaxMessage(commentMsg, '', 'success');

                const token = commentForm.querySelector('input[name="_token"]')?.value || '';
                const body = (commentBody && commentBody.value) ? commentBody.value.trim() : '';
                if (body.length < 3) {
                    setAjaxMessage(commentMsg, 'Մեկնաբանությունը պետք է լինի առնվազն 3 նիշ։', 'error');
                    return;
                }

                if (commentSubmit) commentSubmit.disabled = true;

                try {
                    const { response, data } = await postForm(commentForm.action, { _token: token, body });

                    if (response.status === 401 || response.status === 403) {
                        if (loginUrl) window.location.href = loginUrl;
                        return;
                    }

                    if (!response.ok || !data || data.success !== true) {
                        setAjaxMessage(commentMsg, (data && data.error) ? data.error : 'Չհաջողվեց ավելացնել մեկնաբանությունը։', 'error');
                        return;
                    }

                    const c = data.comment;
                    if (c && commentsList) {
                        const empty = document.getElementById('noComments');
                        if (empty) empty.remove();

                        const wrapper = document.createElement('div');
                        wrapper.className = 'bg-gray-50 border rounded-2xl p-5';

                        const header = document.createElement('div');
                        header.className = 'flex items-center justify-between';

                        const name = document.createElement('div');
                        name.className = 'font-bold text-gray-900 text-sm';
                        name.textContent = c.userName || 'User';

                        const time = document.createElement('div');
                        time.className = 'text-xs text-gray-400';
                        time.textContent = c.createdAt || '';

                        header.appendChild(name);
                        header.appendChild(time);

                        const bodyEl = document.createElement('div');
                        bodyEl.className = 'text-sm text-gray-700 mt-2 whitespace-pre-line';
                        bodyEl.textContent = c.body || '';

                        wrapper.appendChild(header);
                        wrapper.appendChild(bodyEl);

                        commentsList.prepend(wrapper);
                    }

                    if (commentBody) commentBody.value = '';
                    setTimeout(() => setAjaxMessage(commentMsg, '', 'success'), 1500);
                } catch (err) {
                    setAjaxMessage(commentMsg, 'Համակարգային սխալ։', 'error');
                } finally {
                    if (commentSubmit) commentSubmit.disabled = false;
                }
            });
        }
    });
})();


