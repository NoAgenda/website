import naPlayer from './player';
import naSettings from './settings';
import naStorage from './storage';

class PlayerStorage {
  constructor() {
    this.enabled = true;
    this.currentEpisode = null;
    this.lastUpdatedAt = 0;
  }

  initialize() {
    this.loadSubscription = naPlayer.subscribe('load', this.load);
    this.updateSubscription = null;
    this.savePlaybackSubscription = naSettings.subscribe('savePlaybackPosition', value => this.enabled = value !== false);
  }

  load = event => {
    const isActive = this.currentEpisode !== null;
    const isEpisode = event.mediaOptions.type === 'episode';
    const isCurrent = isEpisode && event.mediaOptions.code === this.currentEpisode?.code;

    if (isActive && !isCurrent) {
      this.currentEpisode = null;
      this.lastUpdatedAt = 0;

      this.updateSubscription?.unsubscribe();
    }

    if (!isEpisode) {
      return;
    }

    this.currentEpisode = {
      code: event.mediaOptions.code,
      title: event.mediaOptions.title,
      src: event.mediaOptions.src,
      duration: event.mediaOptions.duration,
      publishedAt: event.mediaOptions.publishedAt,
      url: event.mediaOptions.url,
      cover: event.mediaOptions.cover,
      transcript: event.mediaOptions.transcript,
      playbackPosition: 0,
      playbackFinished: false,
      playbackSavedAt: 0,
    };

    naStorage.persist('episode', this.currentEpisode, true).then(() => {
      this.updateSubscription = naPlayer.subscribe('update', this.update);
    });
  };

  update = (event) => {
    if (!this.enabled) return;

    if (event.timestamp < 30) return;

    if (event.playing) {
      const updatedBefore = Date.now() - (5 * 1000); // Update only every 5 seconds
      if (this.lastUpdatedAt > updatedBefore) return;
    }

    this.currentEpisode.playbackSavedAt = this.lastUpdatedAt = Date.now();

    if (!event.duration || event.timestamp < (event.duration - 60)) {
      this.currentEpisode.playbackPosition = event.timestamp - 5; // Rewind 5 seconds
    } else {
      this.currentEpisode.playbackPosition = 0;
      this.currentEpisode.playbackFinished = true;
    }

    naStorage.persist('episode', this.currentEpisode, true);
  };
}

const naPlayerStorage = window.naPlayerStorage = window.naPlayerStorage || new PlayerStorage();

export default naPlayerStorage;
