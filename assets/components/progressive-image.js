class ProgressiveImageElement extends HTMLElement {
  constructor() {
    super();

    this.onClick = this.onClick.bind(this);
  }

  connectedCallback() {
    this.addEventListener('click', this.onClick);
  }

  disconnectedCallback() {
    this.removeEventListener('click', this.onClick);
  }

  onClick() {
    const src = this.getAttribute('src');
    const alt = this.getAttribute('alt');

    this.innerHTML = `<img src="${src}" alt="${alt}"/>`;

    this.removeEventListener('click', this.onClick);
  }
}

window.customElements.define('na-img', ProgressiveImageElement);
