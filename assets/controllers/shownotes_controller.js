import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  connect() {
    this.element.querySelectorAll('[data-shownotes-details]').forEach(details => {
      details.addEventListener('toggle', () => {
        if (details.hasAttribute('data-shownotes-details-activated')) {
          return;
        }

        const detailsData = JSON.parse(details.dataset.shownotesDetails);

        if (detailsData.type === 'audio') {
          const audio = document.createElement('audio');
          audio.src = detailsData.uri;
          audio.controls = true;

          details.appendChild(audio);
        } else if (detailsData.type === 'image') {
          const image = document.createElement('img');
          image.src = detailsData.uri;
          image.alt = 'Shownotes Image';

          details.appendChild(image);
        }

        details.setAttribute('data-shownotes-details-activated', 'data-shownotes-details-activated');
      });
    });
  }

  disconnect() {
  }
}
