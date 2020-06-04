import {getPlayer, HTMLAudioAwareElement} from '../scripts/player';

class ChapterListElement extends HTMLAudioAwareElement {
  constructor() {
    super();

    this.onAudioStep = this.onAudioStep.bind(this);
    this.onToggleDrafts = this.onToggleDrafts.bind(this);
  }

  connectedCallback() {
    this.activeChapterTimestamp = null;
    this.nextChapterTimestamp = null;
    this.draftsActive = true;
    this.toggleButton = this.querySelector('[data-drafts-toggle]');

    this.toggleButton.addEventListener('click', this.onToggleDrafts);
    getPlayer().addEventListener('audio-step', this.onAudioStep);
  }

  disconnectedCallback() {
    this.toggleButton.removeEventListener('click', this.onToggleDrafts);
    getPlayer().removeEventListener('audio-step', this.onAudioStep);
  }

  onAudioStep(event) {
    const timestamp = event.detail.timestamp;

    if (!this.isActiveSource()) {
      if (this.activeChapterTimestamp !== null) {
        this.querySelectorAll('.chapter-highlight').forEach(chapterElement => chapterElement.classList.remove('chapter-highlight'));

        this.activeChapterTimestamp = null;
      }

      return;
    }

    if (this.activeChapterTimestamp === null) {
      this.updateCurrentChapter();
    }

    if (timestamp >= this.nextChapterTimestamp) {
      this.updateCurrentChapter();
    }
  }

  onToggleDrafts() {
    this.draftsActive = !this.draftsActive;

    this.updateCurrentChapter();

    this.querySelectorAll('[data-draft]').forEach(element => element.style.display = this.draftsActive ? 'block' : 'none');
    this.toggleButton.innerHTML = this.draftsActive ? 'Hide suggested chapters' : 'Show suggested chapters';
  }

  updateCurrentChapter() {
    const timestamp = getPlayer().timestamp;

    let chapterElements = [...this.querySelectorAll('[data-chapter]')];

    if (!chapterElements.length) {
      this.activeChapterTimestamp = false;
    }

    if (!this.draftsActive) {
      chapterElements = chapterElements.filter(chapterElement => !chapterElement.hasAttribute('data-draft'));
    }

    const chapterTimestamps = chapterElements.map(chapterElement => +chapterElement.dataset.timestamp);
    chapterTimestamps.sort((a, b) => a - b);

    this.activeChapterTimestamp = null;
    this.nextChapterTimestamp = null;

    chapterTimestamps.forEach(chapterTimestamp => {
      if (chapterTimestamp <= timestamp) {
        this.activeChapterTimestamp = chapterTimestamp;
      } else if (this.nextChapterTimestamp === null && chapterTimestamp > timestamp) {
        this.nextChapterTimestamp = chapterTimestamp;
      }
    });

    if (this.activeChapterTimestamp !== null) {
      this.querySelectorAll('.chapter-highlight').forEach(chapterElement => chapterElement.classList.remove('chapter-highlight'));

      this.querySelectorAll(`[data-chapter][data-timestamp="${this.activeChapterTimestamp}"]`).forEach(chapterElement => chapterElement.classList.add('chapter-highlight'));
    }
  }
}

window.customElements.define('na-chapter-list', ChapterListElement);
