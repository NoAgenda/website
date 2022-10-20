import Clipboard from 'clipboard';

const clipboard = new Clipboard('[data-clipboard-text]');

clipboard.on('success', event => {
  animateClipboardTrigger(event.trigger, 'clipboard-success');
});

clipboard.on('error', event => {
  animateClipboardTrigger(event.trigger, 'clipboard-error');
});

function animateClipboardTrigger(element, className) {
  element.classList.add(className);
  element.classList.add('clipboard-animate');

  setTimeout(() => {
    element.classList.remove(className);
  }, 1000);

  setTimeout(() => {
    element.classList.remove('clipboard-animate');
  }, 2000);
}
