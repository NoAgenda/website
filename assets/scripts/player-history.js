import {formatTime, getPlayer, HTMLAudioAwareElement} from './player';

/** todo rebuild for global audio player **/
class AudioPlayerHistoryElement extends HTMLAudioAwareElement {
  constructor() {
    super();

    this.onAudioLoaded = this.onAudioLoaded.bind(this);
    this.onAudioStep = this.onAudioStep.bind(this);
    this.onAudioSeek = this.onAudioSeek.bind(this);
  }

  connectedCallback() {
    this.timestamp = 0;
    this.duration = 0;

    this.history = [];

    this.sessionStart = 0;
    this.renderIndex = 0;

    getPlayer().addEventListener('audio-loaded', this.onAudioLoaded);
    getPlayer().addEventListener('audio-step', this.onAudioStep);
    getPlayer().addEventListener('audio-seek', this.onAudioSeek);
  }

  disconnectedCallback() {
    getPlayer().removeEventListener('audio-loaded', this.onAudioLoaded);
    getPlayer().removeEventListener('audio-step', this.onAudioStep);
    getPlayer().removeEventListener('audio-seek', this.onAudioSeek);
  }

  onAudioLoaded() {
    this.duration = getPlayer().duration;
  }

  onAudioStep(event) {
    this.timestamp = event.detail.timestamp;
  }

  onAudioSeek(event) {
    if (this.timestamp === 0) {
      this.sessionStart = event.detail.timestamp;

      if (this.sessionStart > 0) {
        this.add(this.sessionStart);
      }

      return;
    }

    if (this.timestamp >= this.duration) {
      return;
    }

    if ((this.sessionStart + 60) < this.timestamp) {
      this.add(this.timestamp - 10);
    }

    this.sessionStart = event.detail.timestamp;
  }

  add(timestamp) {
    this.history.push(timestamp);

    const html = `
      <div
        class="btn btn-block btn-light d-flex justify-content-between mt-2"
        data-index="${this.renderIndex}"
      >
        <span>Continue listening from ${formatTime(timestamp)}</span>
        <span><span class="fas fa-chevron-right" aria-hidden="true"></span></span>
      </div>
    `;

    this.innerHTML = html + this.innerHTML;

    const button = this.querySelector(`[data-index="${this.renderIndex}"]`);

    button.addEventListener('click', () => {
      getPlayer().seekTimestamp(timestamp);
    });

    ++this.renderIndex;

    this.querySelectorAll('[data-index]').forEach(button => {
      if (button.getAttribute('data-index') < (this.renderIndex - 3)) {
        this.removeChild(button);
      }
    });
  }
}

window.customElements.define('na-audio-history', AudioPlayerHistoryElement);
