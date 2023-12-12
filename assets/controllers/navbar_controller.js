import { Controller } from '@hotwired/stimulus';

import swup from '../scripts/swup';

export default class extends Controller {
  static targets = [
    'controls',
    'controlsButton',
    'menu',
    'menuButton',
  ];

  initialize() {
    this.controlsOpened = false;
    this.menuOpened = false;

    swup.hooks.on('visit:start', () => {
      this.hide();
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth >= 992 && this.menuOpened) {
        this.toggleMenu();
      }
    });
  }

  hide(event) {
    if (event && event.target !== this.element && !event.target.classList.contains('navbar-controls-content')) {
      return;
    }

    if (this.controlsOpened) {
      this.toggleControls();
    }
    if (this.menuOpened) {
      this.toggleMenu();
    }
  }

  toggleControls() {
    if (this.menuOpened) {
      this.toggleMenu();
    }

    this.controlsOpened = !this.controlsOpened;

    if (this.controlsOpened) {
      this.element.classList.add('open');
      this.controlsTarget.classList.remove('hide');
      this.controlsButtonTarget.classList.add('active');
    } else {
      this.element.classList.remove('open');
      this.controlsTarget.classList.add('hide');
      this.controlsButtonTarget.classList.remove('active');
    }
  }

  toggleMenu() {
    if (this.controlsOpened) {
      this.toggleControls();
    }

    this.menuOpened = !this.menuOpened;

    if (this.menuOpened) {
      this.element.classList.add('open');
      this.menuTarget.classList.remove('hide');
      this.menuButtonTarget.classList.add('active');
    } else {
      this.element.classList.remove('open');
      this.menuTarget.classList.add('hide');
      this.menuButtonTarget.classList.remove('active');
    }
  }
}
