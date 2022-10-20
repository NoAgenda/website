import './admin.scss';

window.addEventListener('load', () => {
  // Initialize Bootstrap tooltips
  [...document.querySelectorAll('[data-bs-toggle="tooltip"]')].map(element => {
    return new window.bootstrap.Tooltip(element);
  });
});
