import { Controller } from '@hotwired/stimulus';

import naPlayer from '../services/player';

export default class extends Controller {
  static targets = [
    'icon',
    'text',
  ];

  static values = {
    metadata: Object,
    timestamp: Number,
  }

  connect() {
    this.active = naPlayer.mediaOptions?.src === this.element.href;
    this.originalText = this.textTarget.innerHTML;

    this.loadSubscription = naPlayer.subscribe('load', this.playerLoad);
    this.updateSubscription = null;

    if (this.active && naPlayer.playing) {
      this.setPlaying();
    }

    if (this.timestampValue && !naPlayer.playing) {
      naPlayer.load(this.element.href, this.metadataValue, false);
      naPlayer.seek(this.timestampValue);
    }
  }

  disconnect() {
    this.loadSubscription.unsubscribe();
    this.updateSubscription?.unsubscribe();
  }

  playerLoad = (event) => {
    this.active = event.mediaOptions.src === this.element.href;

    if (!this.active) {
      this.setPaused();

      this.updateSubscription?.unsubscribe();
    } else {
      this.playerUpdate(event);

      this.updateSubscription = naPlayer.subscribe('update', this.playerUpdate);
    }
  };

  playerUpdate = (event) => {
    if (!this.active) return;

    if (event.loading) {
      this.setLoading();
    } else if (event.playing) {
      this.setPlaying();
    } else {
      this.setPaused();
    }
  };

  clicked(event) {
    event?.preventDefault();

    if (naPlayer.mediaOptions?.src === this.element.href) {
      if (naPlayer.loading) {
        naPlayer.abort();
      } else if (naPlayer.playing) {
        naPlayer.pause();
      } else {
        naPlayer.play();
      }
    } else {
      this.setLoading();

      naPlayer.load(this.element.href, this.metadataValue);
    }
  }

  setLoading() {
    this.iconTarget.classList.remove('fa-play', 'fa-pause');
    this.iconTarget.classList.add('fa-spinner', 'fa-spin');

    this.textTarget.innerHTML = 'Loading';
  }

  setPlaying() {
    this.iconTarget.classList.remove('fa-play', 'fa-spinner', 'fa-spin');
    this.iconTarget.classList.add('fa-pause');

    this.textTarget.innerHTML = 'Pause';
  }

  setPaused() {
    this.iconTarget.classList.remove('fa-pause', 'fa-spinner', 'fa-spin');
    this.iconTarget.classList.add('fa-play');

    this.textTarget.innerHTML = this.originalText;
  }
}
