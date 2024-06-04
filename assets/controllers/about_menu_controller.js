import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = [
    'button',
    'menu',
  ];

  connect() {
    this.buttonTarget.setAttribute('role', 'button');
    this.buttonTarget.setAttribute('aria-controls', 'about-menu');
    this.menuTarget.setAttribute('aria-hidden', 'true');

    this.hide();
  }

  toggle() {
    if (this.hidden) {
      this.show();
    } else {
      this.hide();
    }
  }

  show() {
    this.menuTarget.style.maxHeight = `${this.menuTarget.scrollHeight}px`;

    this.buttonTarget.setAttribute('title', 'Hide menu');
    this.buttonTarget.setAttribute('aria-expanded', 'true');
    this.menuTarget.setAttribute('aria-hidden', 'false');

    this.hidden = false;
  }

  hide() {
    this.menuTarget.style.maxHeight = null;

    this.buttonTarget.setAttribute('title', 'Expand menu');
    this.buttonTarget.setAttribute('aria-expanded', 'false');
    this.menuTarget.setAttribute('aria-hidden', 'true');

    this.hidden = true;
  }
}
