import jQuery from 'jquery';

export default class PlayerCorrections {
  constructor(token) {
    this.token = token;

    this.registerEventListeners();
  }

  registerEventListeners() {
    jQuery(document).on('click', '[data-action="part-correction"]', (event) => {
      if (!this.token.isAuthenticated()) {
        this.token.createToken();

        return false;
      }

      let button = jQuery(event.currentTarget);
      let modal = jQuery('#correctionModal');
      let partId = button.data('part-id');

      modal.find('[name$="[part]"]').val(partId);

      modal.modal('show');
    });

    jQuery(document).on('click', '[data-action="part-suggestion"]', (event) => {
      if (!this.token.isAuthenticated()) {
        this.token.createToken();

        return false;
      }

      let button = jQuery(event.currentTarget);
      let modal = jQuery('#suggestionModal');
      let partId = button.data('part-id');

      modal.find('[name$="[part]"]').val(partId);

      modal.modal('show');
    });

    jQuery(document).on('submit', 'form[name="episode_part_correction"]', (event) => {
      event.preventDefault();

      let form = jQuery(event.currentTarget);
      let formData = jQuery(event.currentTarget).serialize();
      let button = form.find('[type="submit"]');

      button.prop('disabled', true);
      button.html(button.html() + ' <span class="fas fa-spinner fa-spin ml-2" aria-hidden="true"></span>');

      form.find('[data-form-error]').remove();

      fetch(form.attr('action'), {
        method: 'post',
        credentials: 'same-origin',
        body: formData,
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
      })
        .then(response => {
          button.prop('disabled', false);
          button.find('svg, span').remove();

          if (response.status === 200) {
            jQuery('#correctionModal').modal('hide');
            jQuery('#successModal').modal('show');

            return;
          }

          if (response.status === 400) {
            response.json().then((data) => {
              let errorCount = 0;

              for (let field in data) {
                if (!data.hasOwnProperty(field)) {
                  continue;
                }

                let errors = data[field];

                let errorSubstitute = form.find('.' + field + '-errors');

                errors.map((message) => {
                  ++errorCount;
                  errorSubstitute.after('<div class="form-text text-danger" data-form-error>' + message + '</div>');
                });
              }

              if (errorCount === 0) {
                form.find('.form-errors').after('<div class="form-text text-danger" data-form-error>An unexpected error occurred.</div>');
              }
            });

            return;
          }

          form.find('.form-errors').after('<div class="form-text text-danger" data-form-error>An unexpected error occurred.</div>');
        })
      ;
    });

    jQuery(document).on('submit', 'form[name="episode_part_suggestion"]', (event) => {
      event.preventDefault();

      let form = jQuery(event.currentTarget);
      let formData = jQuery(event.currentTarget).serialize();
      let button = form.find('[type="submit"]');

      button.prop('disabled', true);
      button.html(button.html() + ' <span class="fas fa-spinner fa-spin ml-2" aria-hidden="true"></span>');

      form.find('[data-form-error]').remove();

      fetch(form.attr('action'), {
        method: 'post',
        credentials: 'same-origin',
        body: formData,
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
      })
        .then(response => {
          button.prop('disabled', false);
          button.find('svg, span').remove();

          if (response.status === 200) {
            jQuery('#suggestionModal').modal('hide');
            jQuery('#successModal').modal('show');

            return;
          }

          if (response.status === 400) {
            response.json().then((data) => {
              let errorCount = 0;

              for (let field in data) {
                if (!data.hasOwnProperty(field)) {
                  continue;
                }

                let errors = data[field];

                let errorSubstitute = form.find('.' + field + '-errors');

                errors.map((message) => {
                  ++errorCount;
                  errorSubstitute.after('<div class="form-text text-danger" data-form-error>' + message + '</div>');
                });
              }

              if (errorCount === 0) {
                form.find('.form-errors').after('<div class="form-text text-danger" data-form-error>An unexpected error occurred.</div>');
              }
            });

            return;
          }

          form.find('.form-errors').after('<div class="form-text text-danger" data-form-error>An unexpected error occurred.</div>');
        })
      ;
    });

    jQuery(document).on('click', '[data-vote-correction]', (event) => {
      if (!this.token.isAuthenticated()) {
        this.token.createToken();

        return false;
      }

      let button = jQuery(event.currentTarget);
      let vote = button.data('vote-correction');
      let correction = button.data('correction-id');

      let data = { vote: vote, correction: correction };
      let encodedData = [];

      for (let key in data) {
        encodedData.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
      }

      fetch('/episode/vote', {
        method: 'post',
        credentials: 'same-origin',
        body: encodedData.join('&').replace(/%20/g, '+'),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
      });

      jQuery('[data-vote-correction][data-correction-id="' + correction + '"]').removeClass('d-inline-block').addClass('d-none');

      let output = jQuery('[data-vote-correction-output="' + vote + '"][data-correction-id="' + correction + '"]');

      let count = +output.html();
      ++count;

      output.html(count);
    });

    jQuery(document).on('change', '[name="episode_part_correction[action]"]', function() {
      let action = this.value;

      jQuery('[data-correction-field]').addClass('d-none');
      jQuery('[data-correction-field="' + action + '"]').removeClass('d-none');
    });

    jQuery('[name="episode_part_correction[action]"]').change();
  }
}
