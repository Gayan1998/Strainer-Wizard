/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.{js,jsx,ts,tsx}",
    "./index.html",
  ],
  theme: {
    extend: {
      colors: {
        purple: {
          500: '#be2edd',
          700: '#4834d4',
          800: '#3b2bb8',
        },
      },
    },
  },
  plugins: [],
}