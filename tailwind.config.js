/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './templates/**/*.php',
    './template-parts/**/*.php',
    './inc/**/*.php',
    './app/**/*.php',
    './resources/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        ink: '#161513',
        paper: '#f4f1ea',
        crimson: '#a11d2e',
        rule: '#d9d2c5',
      },
      fontFamily: {
        serif: ['Newsreader', 'Georgia', 'serif'],
        sans: ['"Source Sans 3"', 'system-ui', 'sans-serif'],
      },
      maxWidth: {
        measure: '72ch',
      },
    },
  },
  plugins: [],
};
