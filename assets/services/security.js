class Security {
  constructor() {
    this.metadata = {
      authenticated: false,
    };
    this.subscriptions = [];
  }

  subscribe(callback) {
    this.subscriptions.push(callback);

    this.dispatch();

    return {
      unsubscribe: () => this.subscriptions.splice(this.subscriptions.indexOf(callback) >>> 0, 1),
    };
  }

  dispatch() {
    this.subscriptions.forEach(callback => callback(this.metadata));
  }

  initialize() {
    this.authenticate();
  }

  authenticate() {
    fetch('/api/auth')
      .then(response => response.json())
      .then(response => {
        if (response) {
          this.metadata = response;

          this.dispatch();
        }
      });
  }
}

const naSecurity = window.naSecurity = window.naSecurity || new Security();

export default naSecurity;
