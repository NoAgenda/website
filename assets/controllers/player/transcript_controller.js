import { Controller } from '@hotwired/stimulus';
import SrtParser from 'srt-parser-2';

import naPlayer from '../../services/player';

const srtParser = new SrtParser();

export default class extends Controller {
  connect() {
    this.active = false;
    this.loaded = false;
    this.uri = null;
    this.transcript = null;
    this.currentLine = null;
    this.nextLine = null;
    this.nextLineFrom = 0;

    this.loadSubscription = naPlayer.subscribe('load', this.playerLoad);
  }

  playerLoad = (event) => {
    this.uri = event.mediaOptions.transcript;
    this.loaded = false;
    this.transcript = null;
    this.currentLine = null;
    this.nextLine = null;
    this.nextLineFrom = 0;

    this.updateSubscription?.unsubscribe();

    this.render();

    if (this.active) {
      this.load();
    }
  };

  playerUpdate = (event) => {
    if (!this.currentLine && !this.nextLine) {
      this.currentLine = this.findCurrentLine(event.timestamp);

      if (this.currentLine) {
        this.render();
      }

      this.nextLine = this.findNextLine(event.timestamp);
      this.nextLineFrom = event.timestamp;
    }

    if (this.currentLine && !match(this.currentLine, event.timestamp)) {
      const currentLine = this.findCurrentLine(event.timestamp);

      if (currentLine && currentLine !== this.currentLine && currentLine !== this.nextLine) {
        this.currentLine = currentLine;

        this.render();
      } else {
        this.currentLine = null;

        this.render();
      }
    }

    if (this.nextLine && match(this.nextLine, event.timestamp)) {
      this.currentLine = this.nextLine;

      this.render();

      this.nextLine = this.findNextLine(event.timestamp);
      this.nextLineFrom = event.timestamp;
    }

    if (this.nextLine && !between(event.timestamp, this.nextLineFrom, this.nextLine.endSeconds)) {
      this.nextLine = this.findNextLine(event.timestamp);
      this.nextLineFrom = event.timestamp;
    }
  };

  findCurrentLine(timestamp) {
    return this.transcript.reduce((current, line) => {
      return match(line, timestamp) && (!current || startsAfter(line, current)) ? line : current;
    }, null);
  }

  findNextLine(timestamp) {
    return this.transcript.reduce((next, line) => {
      return upcoming(line, timestamp) && (!next || startsBefore(line, next)) ? line : next;
    }, null);
  }

  activate() {
    this.active = true;

    if (!this.loaded) {
      this.load();
    } else {
      this.updateSubscription = naPlayer.subscribe('update', this.playerUpdate);
    }
  }

  deactivate() {
    this.active = false;

    this.updateSubscription?.unsubscribe();
  }

  load() {
    fetch(this.uri)
      .then(response => response.text())
      .then(response => {
        this.transcript = srtParser.fromSrt(response);

        this.updateSubscription = naPlayer.subscribe('update', this.playerUpdate);
      });
  }

  render() {
    this.element.innerHTML = this.currentLine ? this.currentLine.text : null;
  }
}

function between(timestamp, from, to) {
  return timestamp >= from && timestamp < to;
}

function match(line, timestamp) {
  return !(+line.startSeconds > timestamp || +line.endSeconds < timestamp);
}

function startsAfter(line, comparison) {
  return +line.startSeconds > +comparison.startSeconds;
}

function startsBefore(line, comparison) {
  return +line.startSeconds < +comparison.startSeconds;
}

function upcoming(line, timestamp) {
  return +line.startSeconds > timestamp;
}
