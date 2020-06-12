import Clipboard from 'clipboard';
import jQuery from 'jquery';

const clipboard = new Clipboard('[data-clipboard-text]');

clipboard.on('success', event => {
  showTooltip(event.trigger, 'Copied to clipboard');
});

clipboard.on('error', event => {
  showTooltip(event.trigger, 'Failed to copy');
});

function showTooltip(button, message) {
  button = jQuery(button);

  button.data('original-title', button.attr('title'));
  button.attr('title', message);

  button.tooltip({animation: true, trigger: 'manual'});
  button.tooltip('show');

  setTimeout(() => {
    button.tooltip('hide');

    button.attr('title', button.data('original-title'));

    setTimeout(() => {
      button.tooltip('dispose');
    }, 1000);
  }, 1000);
}
