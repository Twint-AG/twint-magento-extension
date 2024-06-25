/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './view/**/*.phtml',
    './view/**/*.html',
    './view/**/*.js',
    '.Block/**/*.php',
  ],
  theme: {
    extend: {
      width: {
        '55': '55px',
        '64': '64px'
      },
      height: {
        '55': '55px',
        '64': '64px',
      },
      fontSize: {
        '20': '20px',
        '16': '16px',
        '35': '35px',
      },
    },
  },
  plugins: [],
}

