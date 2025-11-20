/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php",
    "./includes/*.php",
    "./public/*.php",
    "./assets/js/*.js"
  ],
  theme: {
    extend: {
      colors: {
        'dark-blue': '#1e3a8a',
        'primary-blue': '#2563eb',
      },
      animation: {
        'fade-in': 'fadeIn 1s ease-in',
        'slide-in': 'slideIn 0.5s ease-out',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideIn: {
          '0%': { transform: 'translateX(-100%)' },
          '100%': { transform: 'translateX(0)' },
        },
      },
    },
  },
  plugins: [],
}
