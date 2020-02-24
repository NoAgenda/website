import jQuery from 'jquery';

export default class Clipboard {
  constructor() {
    this.registerEventListeners();
  }

  registerEventListeners() {
    jQuery(document).on('click', '[data-clipboard]', (event) => {
      let button = jQuery(event.currentTarget);
      let text = button.data('clipboard');

      if (this.copyToClipboard(text)) {
        this.showTooltip(button, 'Copied to clipboard');
      } else {
        if (button.data('simple')) {
          this.showTooltip(button, 'Copy failed');
        } else {
          const clipboardModal = jQuery('#clipboardModal');

          clipboardModal.find('[data-clipboard-text').html(text);

          clipboardModal.modal('show');
        }
      }
    });
  }

  copyToClipboard(text) {
    text = text.replace(/%3A/g,':');

    if (window.clipboardData && window.clipboardData.setData) {
      // IE specific code path to prevent textarea being shown while dialog is visible.
      return window.clipboardData.setData('Text', text);
    } else if (document.queryCommandSupported && document.queryCommandSupported('copy')) {
      let textarea = document.createElement('textarea');
      textarea.textContent = text;
      textarea.style.position = 'fixed';  // Prevent scrolling to bottom of page in MS Edge.
      document.body.appendChild(textarea);
      textarea.select();
      console.log(textarea.textContent);

      try {
        return document.execCommand('copy');  // Security exception may be thrown by some browsers.
      } catch (ex) {
        console.warn('Copy to clipboard failed.', ex);
        return false;
      } finally {
        document.body.removeChild(textarea);
      }
    }
  }

  showTooltip(button, message) {
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
}
