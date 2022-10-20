class Settings {
  playbackSpeeds = {
    50: '0.5',
    75: '0.75',
    100: '1',
    125: '1.25',
    150: '1.5',
    175: '1.75',
    200: '2',
  };

  skipAmounts = {
    5: '5s',
    15: '15s',
    30: '30s',
    60: '1m',
  };

  skipAmountLabels = {
    5: '5 Seconds',
    15: '15 Seconds',
    30: '30 Seconds',
    60: '1 Minute',
  };

  constructor() {
    this.subscriptions = {};
  }

  subscribe(key, callback) {
    if (!this.subscriptions[key]) {
      this.subscriptions[key] = [];
    }

    this.subscriptions[key].push(callback);

    callback(this.get(key));

    return {
      unsubscribe: () => this.subscriptions[key].splice(this.subscriptions[key].indexOf(callback) >>> 0, 1),
    };
  }

  dispatch(key, value) {
    (this.subscriptions[key] || []).forEach(callback => callback(value));
  }

  get(key) {
    return JSON.parse(localStorage.getItem(key));
  }

  set(key, value) {
    localStorage.setItem(key, JSON.stringify(value));

    this.dispatch(key, value);
  }
}

const naSettings = window.naSettings = window.naSettings || new Settings();

export default naSettings;
