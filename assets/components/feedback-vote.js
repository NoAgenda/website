import tokenManager from '../scripts/token';

class FeedbackVoteElement extends HTMLElement {
  constructor() {
    super();

    this.onClickSupport = this.onClickSupport.bind(this);
    this.onClickReject = this.onClickReject.bind(this);
  }

  connectedCallback() {
    this.supportButton = this.querySelector('[data-support]');
    this.supportCounter = this.querySelector('[data-support-count]');
    this.rejectButton = this.querySelector('[data-reject]');
    this.rejectCounter = this.querySelector('[data-reject-count]');

    if (this.supportButton && this.rejectButton) {
      this.supportButton.addEventListener('click', this.onClickSupport);
      this.rejectButton.addEventListener('click', this.onClickReject);
    }
  }

  disconnectedCallback() {
    if (this.supportButton && this.rejectButton) {
      this.supportButton.removeEventListener('click', this.onClickSupport);
      this.rejectButton.removeEventListener('click', this.onClickReject);
    }
  }

  onClickSupport() {
    const path = this.dataset.url.replace('voteValue', 'support');

    if (!tokenManager.isAuthenticated()) {
      tokenManager.createToken();

      return;
    }

    fetch(path);

    if (this.supportButton && this.rejectButton) {
      this.supportButton.style.display = 'none';
      this.rejectButton.style.display = 'none';
    }

    const newCount = +this.supportCounter.innerHTML + 1;

    this.supportCounter.innerHTML = newCount.toString();
    this.supportCounter.setAttribute('title', newCount === 1 ? `${newCount} producer agrees with this suggestion` : `${newCount} producers agree with this suggestion`);
  }

  onClickReject() {
    const path = this.dataset.url.replace('voteValue', 'reject');

    if (!tokenManager.isAuthenticated()) {
      tokenManager.createToken();

      return;
    }

    fetch(path);

    if (this.supportButton && this.rejectButton) {
      this.supportButton.style.display = 'none';
      this.rejectButton.style.display = 'none';
    }

    const newCount = +this.rejectCounter.innerHTML + 1;

    this.rejectCounter.innerHTML = newCount.toString();
    this.rejectCounter.setAttribute('title', newCount === 1 ? `${newCount} producer questions this suggestion` : `${newCount} producers question this suggestion`);
  }
}

window.customElements.define('na-feedback-vote', FeedbackVoteElement);
