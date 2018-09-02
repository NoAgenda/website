import jQuery from 'jquery';

export default class Archive {
  constructor() {
    this.registerEventListeners();
  }

  registerEventListeners() {
    jQuery(document).on('click', '[data-archive-layout]', (event) => {
      let layout = jQuery(event.currentTarget).attr('data-archive-layout');

      window.location.hash = layout;

      this.updatePaginationLinks(layout);

      this.toggleLayout(layout);
    });

    if (window.location.hash) {
      let layout = window.location.hash.replace('#', '');

      this.updatePaginationLinks(layout);

      this.toggleLayout(layout);
    }
  }

  toggleLayout(layout) {
    let archiveContainer = jQuery('[data-archive-container]');
    let archiveColumns = jQuery('[data-archive-column]');

    if (layout === 'grid') {
      archiveContainer.addClass('row').removeClass('archive-list');
      archiveColumns.addClass('col-sm-6').addClass('col-lg-3');

      jQuery('button[data-archive-layout="grid"]').addClass('btn-primary').removeClass('btn-outline-primary');
      jQuery('button[data-archive-layout!="grid"]').removeClass('btn-primary').addClass('btn-outline-primary');
    }
    if (layout === 'list') {
      archiveContainer.removeClass('row').addClass('archive-list');
      archiveColumns.removeClass('col-sm-6').removeClass('col-lg-3');

      jQuery('button[data-archive-layout="list"]').addClass('btn-primary').removeClass('btn-outline-primary');
      jQuery('button[data-archive-layout!="list"]').removeClass('btn-primary').addClass('btn-outline-primary');
    }
  }

  updatePaginationLinks(layout) {
    let paginationLinks = jQuery('.pagination a.page-link');

    paginationLinks.each((i, link) => {
      let element = jQuery(link);

      if (!element.data('href')) {
        element.data('href', element.attr('href'));
      }

      element.attr('href', element.data('href') + '#' + layout);
    });
  }
}
