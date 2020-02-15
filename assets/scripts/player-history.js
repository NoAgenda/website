import 'waud.js';

import {formatTime} from './player';

class AudioPlayerHistoryElement extends HTMLElement {
  connectedCallback() {
    this.player = document.getElementById(this.getAttribute('data-target'));

    this.timestamp = 0;
    this.duration = 0;

    this.history = [];

    this.sessionStart = 0;
    this.renderIndex = 0;

    this.player.addEventListener('audio-loaded', () => {
      this.duration = this.player.duration;
    });

    this.player.addEventListener('audio-step', event => {
      this.timestamp = event.detail.timestamp;
    });

    this.player.addEventListener('audio-seek', event => {
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
    });
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
      this.player.seekTimestamp(timestamp);
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
