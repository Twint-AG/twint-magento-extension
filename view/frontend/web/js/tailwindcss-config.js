/** @type {import('tailwindcss').Config} */
module.exports = {
  mode: 'jit',
  content: [
    '/Users/taitran/TaiTran/NFQ/projects/twint/magento-extension/view/**/*.phtml',
    '/Users/taitran/TaiTran/NFQ/projects/twint/magento-extension/view/**/*.html',
    '/Users/taitran/TaiTran/NFQ/projects/twint/magento-extension/view/**/*.js',
    '/Users/taitran/TaiTran/NFQ/projects/twint/magento-extension/Block/**/*.php',
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

