import jQuery from 'jquery';

export default class Token {
  constructor() {
    let body = jQuery('body');

    this.authenticated = body.data('authenticated') === true;

    this.registerEventListeners();
  }

  registerEventListeners() {
    jQuery(document).on('click', '[data-token-submit]', (event) => {
      let button = jQuery(event.currentTarget);
      let error =  jQuery('[data-token-error]');

      error.addClass('d-none');
      button.prop('disabled', true);
      button.html(button.html() + ' <span class="fas fa-spinner fa-spin ml-2" aria-hidden="true"></span>');

      fetch('/token', {
        method: 'post',
        credentials: 'same-origin',
      })
        .then(response => {
          button.prop('disabled', false);
          button.find('svg, span').remove();

          if (response.status !== 200) {
            jQuery('[data-token-error]').removeClass('d-none');

            return false;
          }

          this.authenticated = true;

          jQuery('#tokenModal').modal('hide');
        })
      ;
    });
  }

  isAuthenticated() {
    return this.authenticated;
  }

  createToken() {
    jQuery('#tokenModal').modal('show');
  }
}
