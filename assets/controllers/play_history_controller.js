import { Controller } from '@hotwired/stimulus';

import naSettings from '../services/settings';
import naStorage from '../services/storage';
import { formatTimestamp } from '../utilities/timestamps';

export default class extends Controller {
  static targets = [
    'container',
    'template',
  ];

  initialize() {
    this.enabled = false;
    this.episodes = [];

    naStorage.all('episode').then(data => {
      if (!data.length) return;

      data = data.filter(episode => !episode.playbackFinished && episode.playbackPosition > 0);
      data.sort((a, b) => Math.sign(b.playbackSavedAt - a.playbackSavedAt));
      data.splice(4);

      this.episodes = data;

      this.fill();

      if (this.enabled && this.episodes.length) {
        this.show();
      }
    });
  }

  connect() {
    this.savePlaybackSubscription = naSettings.subscribe('savePlaybackPosition', this.saveToggle);
  }

  disconnect() {
    this.savePlaybackSubscription.unsubscribe();
  }

  saveToggle = (value) => {
    this.enabled = value !== false;

    if (this.enabled && this.episodes.length) {
      this.show();
    } else {
      this.hide();
    }
  };

  show() {
    this.element.classList.remove('hide');
    this.element.setAttribute('aria-hidden', 'false');
  }

  hide() {
    this.element.classList.add('hide');
    this.element.setAttribute('aria-hidden', 'true');
  }

  fill() {
    const startSpacer = document.createElement('div');
    startSpacer.classList.add('episode-spacer');
    this.containerTarget.appendChild(startSpacer);

    this.episodes.forEach(episode => {
      const node = this.templateTarget.content.cloneNode(true);

      const anchor = node.querySelector('a');
      anchor.setAttribute('href', episode.url);
      anchor.setAttribute('title', `Listen to No Agenda Show ${episode.title}`);

      const image = node.querySelector('img');
      image.setAttribute('src', episode.cover);
      image.setAttribute('alt', `Cover No Agenda Show ${episode.code}`);

      const title = node.querySelector('h3');
      title.innerHTML = episode.title;

      const remaining = node.querySelector('[data-play-history-template-target="remaining"]');
      remaining.innerHTML = `${formatTimestamp(episode.duration - episode.playbackPosition)} remaining`;

      this.containerTarget.appendChild(node);
    });

    const endSpacer = document.createElement('div');
    endSpacer.classList.add('episode-spacer');
    this.containerTarget.appendChild(endSpacer);
  }
}
