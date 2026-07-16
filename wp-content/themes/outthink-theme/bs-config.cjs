module.exports = {
  proxy: 'http://localhost:8081',
  files: [
    './**/*.php',
    './style.css',
    './theme.json',
    './assets/**/*.{css,js,json,png,jpg,jpeg,gif,svg,webp}',
    './template-parts/**/*.php',
    './inc/**/*.php',
  ],
  ignore: [
    './node_modules/**/*',
    './package-lock.json',
  ],
  open: false,
  notify: false,
  reloadDelay: 250,
};
