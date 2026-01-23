/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./public/**/*.html'],
  theme: {
    extend: {
      colors: {
        joomla: {
          primary: '#0f0f0f',
          secondary: '#000000',
          link: '#0f0f0f',
          success: '#457d54',
          danger: '#c52827',
          warning: '#ffb514',
          'bg-light': '#f5f5f5',
          'bg-dark': '#0f0f0f',
        },
      },
    },
  },
  plugins: [],
};
