import { Controller } from '@hotwired/stimulus';

import naPlayer from '../services/player';

export default class extends Controller {
  static values = {
    metadata: Object,
  };

  connect() {
    this.active = false;
    this.chapters = [];
    this.currentChapter = null;
    this.currentChapterStartPosition = 0;
    this.currentChapterEndPosition = 0;

    [...this.element.children].forEach(child => {
      const chapterTimestamp = child.querySelector('.chapter-timestamp');
      const chapterTitle = child.querySelector('.chapter-title');

      chapterTimestamp.setAttribute('role', 'button');
      chapterTimestamp.setAttribute('title', 'Copy Link for this Chapter to the Clipboard');

      chapterTitle.setAttribute('role', 'button');
      chapterTitle.setAttribute('title', 'Start Playing Chapter');

      chapterTitle.addEventListener('click', () => {
        if (this.active) {
          naPlayer.seek(+child.dataset.startPosition);

          naPlayer.play();
        } else {
          naPlayer.load(this.metadataValue.src, this.metadataValue);

          naPlayer.seek(+child.dataset.startPosition);
        }
      });

      this.chapters.push(child);
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
    if (this.currentChapter && !between(event.timestamp, this.currentChapterStartPosition, this.currentChapterEndPosition)) {
      this.currentChapter.classList.remove('chapter-active');

      this.currentChapter = null;
    }

    if (!this.currentChapter) {
      this.currentChapter = this.findCurrentChapter(event.timestamp);

      if (this.currentChapter) {
        this.currentChapter.classList.add('chapter-active');

        const nextChapter = this.findNextChapter(event.timestamp);
        this.currentChapterStartPosition = this.currentChapter.dataset.startPosition;
        this.currentChapterEndPosition = nextChapter?.dataset.startPosition ?? this.metadataValue.duration;
      }
    }
  };

  findCurrentChapter(timestamp) {
    return this.chapters.reduce((current, chapter) => {
      return match(chapter, timestamp) && (!current || startsAfter(chapter, current)) ? chapter : current;
    }, null);
  }

  findNextChapter(timestamp) {
    return this.chapters.reduce((next, chapter) => {
      return upcoming(chapter, timestamp) && (!next || startsBefore(chapter, next)) ? chapter : next;
    }, null);
  }

  setActive() {
    this.active = true;

    this.updateSubscription = naPlayer.subscribe('update', this.playerUpdate);
  }

  setNotActive() {
    this.active = false;

    this.updateSubscription?.unsubscribe();
  }
}

function between(timestamp, from, to) {
  return timestamp >= from && timestamp < to;
}

function match(chapter, timestamp) {
  return +chapter.dataset.startPosition <= timestamp;
}

function startsAfter(chapter, comparison) {
  return +chapter.dataset.startPosition > +comparison.dataset.startPosition;
}

function startsBefore(chapter, comparison) {
  return +chapter.dataset.startPosition < +comparison.dataset.startPosition;
}

function upcoming(chapter, timestamp) {
  return +chapter.dataset.startPosition > timestamp;
}
