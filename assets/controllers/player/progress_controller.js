import { Controller } from '@hotwired/stimulus';

import naPlayer from '../../services/player';
import { formatTimestamp } from '../../utilities/timestamps';

export default class extends Controller {
  static targets = [
    'area',
    'elapsed',
    'container',
    'pointer',
    'progress',
    'remaining',
    'seek',
  ];

  static values = {
    controls: Boolean,
  };

  connect() {
    this.loadSubscription = naPlayer.subscribe('load', this.playerLoad);
    this.updateSubscription = null;

    if (this.hasAreaTarget) {
      this.selectedTimestamp = 0;

      this.areaTarget.addEventListener('click', this.progressClick);

      this.areaTarget.addEventListener('mouseenter', this.progressMouseEnter);
      this.areaTarget.addEventListener('touchstart', this.progressTouchStart);

      this.areaTarget.addEventListener('mousemove', this.progressMouseMove);
      this.areaTarget.addEventListener('touchmove', this.progressTouchMove);

      this.areaTarget.addEventListener('mouseleave', this.progressMouseLeave);
      this.areaTarget.addEventListener('touchend', this.progressTouchEnd);
    }
  }

  disconnect() {
    this.loadSubscription.unsubscribe();
    this.updateSubscription?.unsubscribe();
  }

  playerLoad = (event) => {
    if (event.mediaOptions.type === 'livestream') {
      this.updateSubscription?.unsubscribe();
      this.updateSubscription = null;
    } else if (event.mediaOptions.type !== 'livestream' && !this.updateSubscription) {
      this.updateSubscription = naPlayer.subscribe('update', this.playerUpdate);
    }
  };

  playerUpdate = (event) => {
    if (this.hasProgressTarget) {
      this.progressTarget.innerHTML = formatTimestamp(event.timestamp);
    }
    if (this.hasRemainingTarget) {
      this.remainingTarget.innerHTML = formatTimestamp(event.remaining);
    }

    const percentage = ((event.timestamp / event.duration) * 100) || 0;
    this.elapsedTarget.style.width = `${percentage}%`;
  };

  progressClick = (event) => {
    const containerRect = this.containerTarget.getBoundingClientRect();

    let distance = event.pageX - containerRect.left;
    const percentage = distance / containerRect.width;
    let newTimestamp = percentage * naPlayer.duration;

    if (newTimestamp < 0) {
      newTimestamp = 0;
    } else if (newTimestamp > naPlayer.duration) {
      newTimestamp = naPlayer.duration - 1;
    }

    naPlayer.seek(newTimestamp);
  };

  progressMouseEnter = (event) => {
    this.inputStart(event.pageX);
  };

  progressTouchStart = (event) => {
    event.preventDefault();

    const touch = event.changedTouches[0];

    this.inputStart(touch.pageX);
  };

  progressMouseMove = (event) => {
    this.inputMove(event.pageX);
  };

  progressTouchMove = (event) => {
    event.preventDefault();

    const touch = event.changedTouches[0];

    this.inputMove(touch.pageX);
  };

  progressMouseLeave = () => {
    this.inputEnd(false);
  };

  progressTouchEnd = () => {
    this.inputEnd(true);
  };

  inputStart(pageX) {
    this.pointerTarget.classList.remove('hide');
    this.remainingTarget.classList.add('hide');
    this.progressTarget.classList.add('hide');
    this.seekTarget.classList.remove('hide');

    this.inputMove(pageX);
  }

  inputMove(pageX) {
    const containerRect = this.containerTarget.getBoundingClientRect();

    let distance = pageX - containerRect.left;
    const percentage = distance / containerRect.width;
    let newTimestamp = percentage * naPlayer.duration;

    if (newTimestamp < 0) {
      distance = 1;
      newTimestamp = 0;
    } else if (newTimestamp > naPlayer.duration) {
      distance = containerRect.width - 1;
      newTimestamp = naPlayer.duration;
    }

    this.pointerTarget.style.left = (distance - 3) + 'px';

    this.selectedTimestamp = newTimestamp;
    this.seekTarget.innerHTML = formatTimestamp(newTimestamp);

    const seekRect = this.seekTarget.getBoundingClientRect();
    let seekLeft = distance - (seekRect.width / 2) - 1;
    const maxSeekLeft = containerRect.width - seekRect.width;

    if (seekLeft < 0) {
      seekLeft = 0;
    } else if (seekLeft > maxSeekLeft) {
      seekLeft = maxSeekLeft;
    }

    this.seekTarget.style.left = seekLeft + 'px';
  }

  inputEnd(touch) {
    this.pointerTarget.classList.add('hide');
    this.remainingTarget.classList.remove('hide');
    this.progressTarget.classList.remove('hide');
    this.seekTarget.classList.add('hide');

    if (touch) {
      naPlayer.seek(this.selectedTimestamp);
    }
  }
}
