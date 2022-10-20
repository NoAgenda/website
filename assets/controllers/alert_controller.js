import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  close() {
    this.element.classList.add('hide');
    this.element.setAttribute('aria-hidden', 'true');
  }
}
