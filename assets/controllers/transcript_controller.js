import { Controller } from '@hotwired/stimulus';

import naPlayer from '../services/player';

export default class extends Controller {
  static targets = [
    'autoScrollButton',
    'grid',
  ];

  static values = {
    metadata: Object,
  };

  connect() {
    this.active = false;
    this.autoScroll = false;
    this.lines = [];
    this.currentLine = null;
    this.nextLine = null;
    this.nextLineSince = 0;

    [...this.gridTarget.children].forEach(child => {
      if (child.classList.contains('transcript-text')) {
        child.setAttribute('role', 'button');
        child.setAttribute('title', 'Start Playing from this Position');

        child.addEventListener('click', () => {
          if (this.active) {
            naPlayer.seek(+child.dataset.startPosition);

            naPlayer.play();
          } else {
            naPlayer.load(this.metadataValue.src, this.metadataValue);

            naPlayer.seek(+child.dataset.startPosition);
          }
        });

        this.lines.push(child);
      } else if (child.classList.contains('transcript-timestamp')) {
        child.setAttribute('role', 'button');
        child.setAttribute('title', 'Copy Link for this Position to the Clipboard');
      }
    });

    this.loadSubscription = naPlayer.subscribe('load', this.playerLoad);

    if (naPlayer.mediaOptions?.src === this.metadataValue.src) {
      this.setActive();
    }
  }

  disconnect() {
    this.loadSubscription.unsubscribe();
    this.updateSubscription?.unsubscribe();
  }

  playerLoad = (event) => {
    if (!this.active && event.mediaOptions.src === this.metadataValue.src) {
      this.setActive();
    } else if (this.active && event.mediaOptions.src !== this.metadataValue.src) {
      this.setNotActive();
    }
  }

  playerUpdate = (event) => {
    if (!this.currentLine && !this.nextLine) {
      this.currentLine = this.findCurrentLine(event.timestamp);

      if (this.currentLine) {
        this.currentLine.classList.add('transcript-active');
      }

      this.nextLine = this.findNextLine(event.timestamp);
      this.nextLineSince = event.timestamp;
    }

    if (this.currentLine && !match(this.currentLine, event.timestamp)) {
      this.currentLine.classList.remove('transcript-active');

      const currentLine = this.findCurrentLine(event.timestamp);

      if (currentLine && currentLine !== this.currentLine && currentLine !== this.nextLine) {
        this.currentLine = currentLine;

        this.currentLine.classList.add('transcript-active');

        this.scroll();
      } else {
        this.currentLine = null;
      }
    }

    if (this.nextLine && match(this.nextLine, event.timestamp)) {
      if (this.currentLine) {
        this.currentLine.classList.remove('transcript-active');
      }

      this.currentLine = this.nextLine;

      this.currentLine.classList.add('transcript-active');

      this.scroll();

      this.nextLine = this.findNextLine(event.timestamp);
      this.nextLineSince = event.timestamp;
    }

    if (this.nextLine && !between(event.timestamp, this.nextLineSince, +this.nextLine.dataset.startPosition)) {
      this.nextLine = this.findNextLine(event.timestamp);
      this.nextLineSince = event.timestamp;
    }
  };

  findCurrentLine(timestamp) {
    return this.lines.reduce((current, line) => {
      return match(line, timestamp) && (!current || startsAfter(line, current)) ? line : current;
    }, null);
  }

  findNextLine(timestamp) {
    return this.lines.reduce((next, line) => {
      return upcoming(line, timestamp) && (!next || startsBefore(line, next)) ? line : next;
    }, null);
  }

  setActive() {
    this.active = true;

    this.autoScrollButtonTarget.classList.remove('hide');

    this.updateSubscription = naPlayer.subscribe('update', this.playerUpdate);
  }

  setNotActive() {
    this.active = false;

    this.autoScrollButtonTarget.classList.add('hide');

    this.updateSubscription?.unsubscribe();
  }

  scroll() {
    if (this.autoScroll && this.currentLine) {
      window.scroll({
        top: this.currentLine.getBoundingClientRect().top + window.scrollY - 32,
        behavior: 'smooth',
      });
    }
  }

  toggleAutoScroll() {
    this.autoScroll = !this.autoScroll;

    if (this.autoScroll) {
      if (naPlayer.playing) {
        this.scroll();
      }

      this.autoScrollButtonTarget.innerHTML = 'Disable Auto Scroll';
    } else {
      this.autoScrollButtonTarget.innerHTML = 'Auto Scroll';
    }
  }
}

function between(timestamp, from, to) {
  return timestamp >= from && timestamp < to;
}

function match(line, timestamp) {
  return !(+line.dataset.startPosition > timestamp || +line.dataset.endPosition < timestamp);
}

function startsAfter(line, comparison) {
  return +line.dataset.startPosition > +comparison.dataset.startPosition;
}

function startsBefore(line, comparison) {
  return +line.dataset.startPosition < +comparison.dataset.startPosition;
}

function upcoming(line, timestamp) {
  return +line.dataset.startPosition > timestamp;
}
