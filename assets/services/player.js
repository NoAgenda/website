import naSettings from './settings';

class Player {
  constructor() {
    this.mediaOptions = null;
    this.mediaPlayer = null;
    this.subscriptions = {};

    this.duration = null;
    this.loading = false;
    this.playing = false;
  }

  initialize() {
    this.mediaPlayer = new Audio();

    this.mediaPlayer.addEventListener('emptied', this.mediaReset);
    this.mediaPlayer.addEventListener('canplaythrough', this.mediaCanPlay);
    this.mediaPlayer.addEventListener('loadedmetadata', this.mediaLoad);
    this.mediaPlayer.addEventListener('play', this.mediaUpdate);
    this.mediaPlayer.addEventListener('pause', this.mediaUpdate);
    this.mediaPlayer.addEventListener('timeupdate', this.mediaUpdate);

    naSettings.subscribe('playbackSpeed', this.playbackSpeedUpdate);
  }

  mediaReset = () => {

  };

  mediaLoad = () => {
    if (this.mediaOptions.type === 'livestream') {
      this.duration = null;
    } else {
      this.duration = Math.round(this.mediaPlayer.duration);
    }

    this.dispatchMediaEvent('load');
  };

  mediaCanPlay = () => {
    this.loading = false;

    this.dispatchMediaEvent('update');
  };

  mediaUpdate = () => {
    this.playing = !this.mediaPlayer.paused;

    if (this.loading && this.playing) {
      this.loading = false;
    }

    this.dispatchMediaEvent('update');
  };

  playbackSpeedUpdate = (value) => {
    if (this.mediaOptions?.type === 'episode') {
      this.mediaPlayer.playbackRate = value / 100;
    }
  };

  subscribe(eventId, callback) {
    if (!this.subscriptions[eventId]) {
      this.subscriptions[eventId] = [];
    }

    this.subscriptions[eventId].push(callback);

    return {
      unsubscribe: () => this.subscriptions[eventId].splice(this.subscriptions[eventId].indexOf(callback) >>> 0, 1),
    };
  }

  dispatch(eventId, event) {
    (this.subscriptions[eventId] || []).forEach(callback => callback(event));
  }

  dispatchMediaEvent(eventId) {
    const timestamp = this.mediaPlayer.currentTime;

    const event = {
      mediaOptions: this.mediaOptions,
      playing: this.playing,
      loading: this.loading,

      duration: this.duration,
      remaining: this.duration - timestamp,
      timestamp: timestamp,
    };

    this.dispatch(eventId, event);
  }

  load(src, options, play = true) {
    this.mediaPlayer.src = src;
    this.mediaOptions = options;

    if (options.type === 'episode') {
      this.mediaPlayer.playbackRate = (naSettings.get('playbackSpeed') ?? 100) / 100;
    } else {
      this.mediaPlayer.playbackRate = 1;
    }

    this.loading = true;

    if (play) {
      this.mediaPlayer.play();
    }
  }

  play() {
    this.mediaPlayer.play();
  }

  pause() {
    this.mediaPlayer.pause();

    if (this.loading) {
      this.loading = false;

      this.dispatchMediaEvent('update');
    }
  }

  seek(timestamp) {
    this.mediaPlayer.currentTime = timestamp;

    if (this.playing) {
      this.loading = true;
    }

    this.dispatchMediaEvent('update');
  }

  seekRelative(interval) {
    this.mediaPlayer.currentTime += interval;
  }
}

const naPlayer = window.naPlayer = window.naPlayer || new Player();

export default naPlayer;
