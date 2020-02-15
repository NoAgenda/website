import jQuery from 'jquery';
import 'waud.js';

class AudioPlayerElement extends HTMLElement {
  connectedCallback() {
    this.src = this.getAttribute('data-src');
    this.timestamp = +this.getAttribute('data-timestamp') || 0;
    this.playing = false;
    this.duration = 0;

    Waud.init();

    this.sound = new WaudSound(this.src, {
      autoplay: false,
      loop: false,
      webaudio: false,
      onload: () => {
        this.duration = this.sound.getDuration();

        this.dispatchEvent(new Event('audio-loaded'));

        this.dispatchEvent(new CustomEvent('audio-seek', {
          detail: {
            timestamp: this.timestamp,
          },
        }));
      },
    });

    requestAnimationFrame(this.step.bind(this));
  }

  play() {
    if (this.sound.isPlaying()) {
      return;
    }

    this.sound.play();

    const timestamp = this.sound.getTime() || 0;

    if (timestamp !== this.timestamp) {
      this.sound.setTime(this.timestamp);
    }

    this.dispatchEvent(new Event('audio-start'));
  }

  pause() {
    if (!this.sound.isPlaying()) {
      return;
    }

    this.sound.pause();

    this.dispatchEvent(new Event('audio-pause'));
  }

  seekTimestamp(timestamp) {
    if (this.sound.isPlaying()) {
      this.sound.setTime(timestamp);
    } else {
      this.timestamp = timestamp;
    }

    this.dispatchEvent(new CustomEvent('audio-seek', {
      detail: {
        timestamp: timestamp,
      },
    }));
  }

  step() {
    if (this.sound.isPlaying()) {
      const timestamp = this.sound.getTime() || 0;

      if (this.timestamp !== timestamp) {
        this.timestamp = timestamp;

        this.dispatchEvent(new CustomEvent('audio-step', {
          detail: {
            timestamp: timestamp,
          },
        }));
      }
    }

    requestAnimationFrame(this.step.bind(this));
  }
}

class AudioPlayButtonElement extends HTMLElement {
  connectedCallback() {
    this.player = document.getElementById(this.getAttribute('data-target'));

    this.playButton = this.querySelector('[data-play-button]');
    this.pauseButton = this.querySelector('[data-pause-button]');

    this.playButton.addEventListener('click', () => {
      this.player.play();
    });

    this.pauseButton.addEventListener('click', () => {
      this.player.pause();
    });

    this.player.addEventListener('audio-start', () => {
      this.updateButtons(true);
    });

    this.player.addEventListener('audio-pause', () => {
      this.updateButtons(false);
    });
  }

  updateButtons(playing) {
    this.playButton.setAttribute('aria-hidden', ariaBoolean(playing));
    this.pauseButton.setAttribute('aria-hidden', ariaBoolean(!playing));

    if (playing) {
      this.playButton.classList.add('d-none');
      this.pauseButton.classList.remove('d-none');
    } else {
      this.playButton.classList.remove('d-none');
      this.pauseButton.classList.add('d-none');
    }
  }
}

class AudioProgressButtonElement extends HTMLElement {
  connectedCallback() {
    this.player = document.getElementById(this.getAttribute('data-target'));

    const direction = this.getAttribute('data-direction');
    const seconds = this.getAttribute('data-seconds');

    const diff = direction === 'forward' ? +seconds : -seconds;

    this.addEventListener('click', () => {
      this.player.seekTimestamp(this.player.timestamp + diff);
    });
  }
}

class AudioTimestampButtonElement extends HTMLElement {
  connectedCallback() {
    this.player = document.getElementById(this.getAttribute('data-target'));

    const seconds = this.getAttribute('data-timestamp');

    this.addEventListener('click', () => {
      this.player.seekTimestamp(seconds);

      if (!this.player.playing) {
        this.player.play();
      }
    });
  }
}

class AudioProgressBarElement extends HTMLElement {
  connectedCallback() {
    this.player = document.getElementById(this.getAttribute('data-target'));

    this.progress = this.querySelector('[data-progress]');
    this.duration = this.querySelector('[data-duration]');
    this.seek = this.querySelector('[data-seek]');
    this.progressBar = this.querySelector('[data-progress-bar]');
    this.durationBar = this.querySelector('[data-duration-bar]');
    this.pointer = this.querySelector('[data-pointer]');

    this.movingNewTimestamp = 0;

    this.duration.innerHTML = formatTime(this.player.duration);
    this.player.addEventListener('audio-loaded', () => {
      this.duration.innerHTML = formatTime(this.player.duration);
    });

    this.stepListener = event => {
      const percentage = ((event.detail.timestamp / this.player.duration) * 100) || 0;

      this.progress.innerHTML = formatTime(event.detail.timestamp);
      this.progressBar.style.width = percentage + '%';
    };

    this.player.addEventListener('audio-step', this.stepListener);
    this.player.addEventListener('audio-seek', this.stepListener);

    this.enterListener = pageX => {
      this.pointer.classList.remove('d-none');

      this.duration.classList.add('d-none');
      this.progress.classList.add('d-none');

      this.querySelectorAll('[data-pointer-hide]').forEach(element => element.classList.add('d-none'));

      this.seek.classList.remove('d-none');

      this.moveListener(pageX);
    };

    this.durationBar.addEventListener('mouseenter', event => this.enterListener(event));
    this.durationBar.addEventListener('touchstart', event => {
      event.preventDefault();

      const touch = event.changedTouches[0];

      this.enterListener(touch.pageX);
    });

    this.leaveListener = touch => {
      this.pointer.classList.add('d-none');

      this.duration.classList.remove('d-none');
      this.progress.classList.remove('d-none');

      this.querySelectorAll('[data-pointer-hide]').forEach(element => element.classList.remove('d-none'));

      this.seek.classList.add('d-none');

      if (touch) {
        this.player.seekTimestamp(this.movingNewTimestamp);
      }
    };

    this.durationBar.addEventListener('mouseleave', () => this.leaveListener(false));
    this.durationBar.addEventListener('touchend', () => this.leaveListener(true));

    this.moveListener = (pageX) => {
      const durationBarRect = this.durationBar.getBoundingClientRect();

      let distance = pageX - durationBarRect.left;
      const percentage = distance / durationBarRect.width;
      let newTimestamp = percentage * this.player.duration;

      if (newTimestamp < 0) {
        distance = 1;
        newTimestamp = 0;
      } else if (newTimestamp > this.player.duration) {
        distance = durationBarRect.width - 1;
        newTimestamp = this.player.duration;
      }

      this.pointer.style.left = (distance - 1) + 'px';

      this.movingNewTimestamp = newTimestamp;
      this.seek.innerHTML = formatTime(newTimestamp);

      if (!this.seek.hasAttribute('data-still')) {
        const seekRect = this.seek.getBoundingClientRect();
        let seekLeft = distance - (seekRect.width / 2) - 1;
        const maxSeekLeft = durationBarRect.width - seekRect.width;

        if (seekLeft < 0) {
          seekLeft = 0;
        } else if (seekLeft > maxSeekLeft) {
          seekLeft = maxSeekLeft;
        }

        this.seek.style.left = seekLeft + 'px';
      }
    };

    this.durationBar.addEventListener('mousemove', event => this.moveListener(event.pageX));
    this.durationBar.addEventListener('touchmove', event => {
      event.preventDefault();

      const touch = event.changedTouches[0];

      this.moveListener(touch.pageX);
    });

    this.durationBar.addEventListener('click', event => {
      const durationBarRect = this.durationBar.getBoundingClientRect();

      let distance = event.pageX - durationBarRect.left;
      const percentage = distance / durationBarRect.width;
      let newTimestamp = percentage * this.player.duration;

      if (newTimestamp < 0) {
        newTimestamp = 0;
      } else if (newTimestamp > this.player.duration) {
        newTimestamp = this.player.duration - 1;
      }

      this.player.seekTimestamp(newTimestamp);
    });
  }
}

jQuery(document).ready(() => {
  const player = document.getElementById('episodePlayer');

  if (!player) {
    return;
  }

  const updateInterface = timestamp => {
    let duration = player.duration;
    let progress = (((timestamp / duration) * 100) || 0) + '%';

    jQuery('[data-player-data="timer"]').text(formatTime(timestamp));
    jQuery('[data-player-data="progress"]').css('width', progress);

    jQuery('[data-player-data="timer-attribute"]').each((index, element) => {
      element = jQuery(element);
      let attribute = element.data('player-attribute');

      if (typeof element.data('original-' + attribute) === 'undefined') {
        element.data('original-' + attribute, element.data(attribute) || element.attr(attribute));
      }

      if (!element.data('original-' + attribute)) {
        return;
      }

      let original = element.data('original-' + attribute);
      element.attr(attribute, original.replace('t=0:00', 't=' + formatTime(timestamp)));
    });
  };

  player.addEventListener('audio-seek', event => updateInterface(event.detail.timestamp));
  player.addEventListener('audio-step', event => updateInterface(event.detail.timestamp));
});

function ariaBoolean(value) {
  return value ? 'true' : 'false';
}

export function formatTime(value) {
  let hours = Math.floor(value / 60 / 60) || 0;
  let minutes = Math.floor((value - (hours * 60 * 60)) / 60) || 0;
  let seconds = (value - (minutes * 60) - (hours * 60 * 60)) || 0;

  if (hours > 0) {
    return hours + ':' + (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + Math.trunc(seconds);
  }

  return minutes + ':' + (seconds < 10 ? '0' : '') + Math.trunc(seconds);
}

export function serializeTime(value) {
  let values = value.split(':');
  let result = false;

  if (values.length > 2) {
    result = (+values[0]) * 60 * 60 + (+values[1]) * 60 + (+values[2]);
  }
  else if (values.length === 2) {
    result = (+values[0]) * 60 + (+values[1]);
  }

  return result;
}

window.customElements.define('na-audio', AudioPlayerElement);
window.customElements.define('na-audio-play', AudioPlayButtonElement);
window.customElements.define('na-audio-seek', AudioProgressButtonElement);
window.customElements.define('na-audio-timestamp', AudioTimestampButtonElement);
window.customElements.define('na-audio-progress', AudioProgressBarElement);
