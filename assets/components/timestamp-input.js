import {formatTime, getPlayer, HTMLAudioAwareElement, serializeTime} from '../scripts/player';

class TimestampInputElement extends HTMLAudioAwareElement {
  constructor() {
    super();

    this.onInput = this.onInput.bind(this);
    this.onRetrieve = this.onRetrieve.bind(this);
    this.onTrackLoaded = this.onTrackLoaded.bind(this);
  }

  connectedCallback() {
    this.input = this.querySelector('input');
    this.retrieveButton = this.querySelector('[data-retrieve]');
    this.playButton = this.querySelector('[data-play]');

    if (this.isActiveSource()) {
      this.retrieveButton.style.display = 'block';
    }

    this.input.addEventListener('input', this.onInput);
    this.input.addEventListener('change', this.onInput);
    this.retrieveButton.addEventListener('click', this.onRetrieve);
    getPlayer().addEventListener('track-loaded', this.onTrackLoaded);
  }

  disconnectedCallback() {
    this.input.removeEventListener('input', this.onInput);
    this.input.removeEventListener('change', this.onInput);
    this.retrieveButton.removeEventListener('click', this.onRetrieve);
    getPlayer().removeEventListener('track-loaded', this.onTrackLoaded);
  }

  onInput(event) {
    this.playButton.dataset.timestamp = serializeTime(event.target.value);
  }

  onRetrieve() {
    this.input.value = formatTime(getPlayer().timestamp);
    this.playButton.dataset.timestamp = serializeTime(this.input.value);
  }

  onTrackLoaded() {
    this.retrieveButton.style.display = this.isActiveSource() ? 'block' : 'none';
  }
}

window.customElements.define('na-timestamp-input', TimestampInputElement);
