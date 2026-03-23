/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./templates/**/*.twig",
    "./src/**/*.php"
  ],
  theme: {
    extend: {
      fontSize: {
        xs: ['0.8rem', { lineHeight: '1.25rem' }],
        sm: ['0.875rem', { lineHeight: '1.375rem' }],
      },
    }
  },
  plugins: [
    require('@tailwindcss/typography'),
  ]
};


