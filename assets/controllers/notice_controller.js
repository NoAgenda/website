import { Controller } from '@hotwired/stimulus';
import naSettings from '../services/settings';

export default class extends Controller {
  static values = {
    id: String,
  };

  connect() {
    if (!naSettings.get(`${this.idValue}Closed`)) {
      this.show();
    }
  }

  close() {
    naSettings.set(`${this.idValue}Closed`, true);

    this.hide();
  }

  show() {
    this.element.classList.remove('hide');
    this.element.setAttribute('aria-hidden', 'false');
  }

  hide() {
    this.element.classList.add('hide');
    this.element.setAttribute('aria-hidden', 'true');
  }
}
