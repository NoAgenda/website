import { Controller } from '@hotwired/stimulus';

import naPlayer from '../services/player';
import naSettings from '../services/settings';
import naStorage from '../services/storage';
import { formatTimestamp } from '../utilities/timestamps';

export default class extends Controller {
  static targets = [
    'icon',
    'text',
    'timestamp',
  ];

  static values = {
    metadata: Object,
  }

  initialize() {
    this.enabled = false;
    this.loading = false;
    this.playbackPosition = null;
  }

  connect() {
    if (naPlayer.mediaOptions?.src === this.element.href) {
      return;
    }

    this.loadSubscription = naPlayer.subscribe('load', this.playerLoad);
    this.savePlaybackSubscription = naSettings.subscribe('savePlaybackPosition', this.saveToggle);

    naStorage.find('episode', this.metadataValue.code).then(episode => {
      if (!episode) return;

      if (episode.playbackPosition === 0) return;

      this.playbackPosition = episode.playbackPosition;

      if (this.enabled) {
        this.show();
      }
    });
  }

  disconnect() {
    this.loadSubscription.unsubscribe();
    this.savePlaybackSubscription.unsubscribe();
  }

  saveToggle = (value) => {
    this.enabled = value !== false;

    if (this.enabled && this.playbackPosition) {
      this.show();
    } else {
      this.hide();
    }
  };

  playerLoad = () => {
    if (this.loading) {
      this.hide();
    }
  };

  clicked(event) {
    event.preventDefault();

    naPlayer.load(this.element.href, this.metadataValue);
    naPlayer.seek(this.playbackPosition);

    this.loading = true;
    this.setLoading();
  }

  show() {
    this.element.classList.remove('btn-resume');

    const timestamp = formatTimestamp(this.playbackPosition);

    this.element.setAttribute('title', `Resume from ${timestamp}`);
    this.textTarget.innerHTML = `Resume from ${timestamp}`;
    if (this.hasTimestampTarget) {
      this.timestampTarget.innerHTML = timestamp;
    }
  }

  hide() {
    this.element.classList.add('btn-resume');
  }

  setLoading() {
    this.iconTarget.classList.remove('fa-play');
    this.iconTarget.classList.add('fa-spinner', 'fa-spin');
  }
}
