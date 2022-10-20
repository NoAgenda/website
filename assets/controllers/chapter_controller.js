import { Controller } from '@hotwired/stimulus';

import naPlayer from '../services/player';

export default class extends Controller {
  static targets = [
    'info',
  ];

  static values = {
    metadata: Object,
    timestamp: Number,
  };

  connect() {
    this.open = false;
  }

  play() {
    if (naPlayer.mediaOptions?.src === this.metadataValue.src) {
      naPlayer.seek(this.timestampValue);

      naPlayer.play();
    } else {
      naPlayer.load(this.metadataValue.src, this.metadataValue);

      naPlayer.seek(this.timestampValue);
    }
  }

  toggle() {
    this.open = !this.open;

    if (this.open) {
      this.infoTarget.classList.remove('hide');
    } else {
      this.infoTarget.classList.add('hide');
    }
  }
}
