class ChapterListElement extends HTMLElement {
  constructor() {
    super();

    this.onToggleDrafts = this.onToggleDrafts.bind(this);
  }

  connectedCallback() {
    this.draftsActive = true;
    this.toggleButton = this.querySelector('[data-drafts-toggle]');

    this.toggleButton.addEventListener('click', this.onToggleDrafts);
  }

  disconnectedCallback() {
    this.toggleButton.removeEventListener('click', this.onToggleDrafts);
  }

  onToggleDrafts() {
    this.draftsActive = !this.draftsActive;

    this.querySelectorAll('[data-draft]').forEach(element => element.style.display = this.draftsActive ? 'block' : 'none');
    this.toggleButton.innerHTML = this.draftsActive ? 'Hide suggested chapters' : 'Show suggested chapters';
  }
}

window.customElements.define('na-chapter-list', ChapterListElement);
