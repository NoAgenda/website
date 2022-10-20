import { Controller } from '@hotwired/stimulus';

import naPlayer from '../services/player';
import { formatTimestamp, parseTimestamp } from '../utilities/timestamps';

export default class extends Controller {
  static targets = [
    'input',
  ];

  static values = {
    metadata: Object,
  }

  connect() {
    this.active = naPlayer.mediaOptions?.src === this.metadataValue.src;

    this.loadSubscription = naPlayer.subscribe('load', this.playerLoad);
  }

  disconnect() {
    this.loadSubscription.unsubscribe();
  }

  playerLoad = (event) => {
    this.active = event.mediaOptions.src === this.metadataValue.src;
  };

  readInput() {
    return parseTimestamp(this.inputTarget.value);
  }

  play() {
    if (!this.active) {
      naPlayer.load(this.metadataValue.src, this.metadataValue);
    }

    naPlayer.seek(this.readInput());
  }

  paste() {
    if (this.active) {
      this.inputTarget.value = formatTimestamp(naPlayer.mediaPlayer.currentTime);
    }
  }

  minusFive() {
    this.inputTarget.value = formatTimestamp(this.readInput() - 5);
  }

  minusOne() {
    this.inputTarget.value = formatTimestamp(this.readInput() - 1);
  }

  plusFive() {
    this.inputTarget.value = formatTimestamp(this.readInput() + 5);
  }

  plusOne() {
    this.inputTarget.value = formatTimestamp(this.readInput() + 1);
  }
}
