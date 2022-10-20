import { Controller } from '@hotwired/stimulus';
import naSettings from '../services/settings';

export default class extends Controller {
  static values = {
    type: String,
  };

  static targets = [
    'button',
    'help',
  ];

  connect() {
    this.supported = 'Notification' in window && 'vapidPublicKey' in window;
    this.enabled = naSettings.get(`${this.typeValue}Notifications`) === true;

    this.settingSubscription = naSettings.subscribe(`${this.typeValue}Notifications`, this.settingUpdate);

    if (!this.supported) {
      this.setUnsupported();
    } else {
      document.querySelectorAll('.notifications-hide').forEach(element => element.classList.remove('notifications-hide'));
    }
  }

  disconnect() {
    this.settingSubscription.unsubscribe();
  }

  settingUpdate = (value) => {
    this.enabled = value;

    this.clearHelp();
    this.restore();
  }

  async toggle() {
    if (!this.supported) {
      return;
    }

    if (Notification.permission === 'denied') {
      this.setDenied();
      return;
    }

    if (Notification.permission !== 'granted') {
      const permission = await Notification.requestPermission();

      if (permission !== 'granted') {
        this.setError();
        return;
      }
    }

    this.clearHelp();
    this.setLoading();

    if (!naSettings.get('notificationSubscription')) {
      await this.activate();
    }

    if (!this.enabled) {
      await this.subscribe();
    } else {
      await this.unsubscribe();
    }
  }

  activate() {
    return new Promise((resolve, reject) => {
      navigator.serviceWorker.ready
        .then(swRegistration => swRegistration.pushManager.subscribe({
          applicationServerKey: urlBase64ToUint8Array(window.vapidPublicKey),
          userVisibleOnly: true,
        }))
        .then(subscription => {
          naSettings.set('notificationSubscription', subscription);

          resolve();
        })
        .catch((error) => {
          this.setError();
          this.restore();

          console.error(error);
          reject();
        });
    });
  }

  async subscribe() {
    await fetch(`/api/notifications/subscribe/${this.typeValue}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(naSettings.get('notificationSubscription')),
    }).then((response) => {
      if (response.status >= 400) {
        throw new Error();
      }

      naSettings.set(`${this.typeValue}Notifications`, true);
    }).catch((error) => {
      console.error(error);

      this.setError();
      this.restore();
    });
  }

  async unsubscribe() {
    await fetch(`/api/notifications/unsubscribe/${this.typeValue}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(naSettings.get('notificationSubscription')),
    }).then((response) => {
      if (response.status >= 400) {
        throw new Error();
      }

      naSettings.set(`${this.typeValue}Notifications`, false);
    }).catch((error) => {
      console.error(error);

      this.setError();
      this.restore();
    });
  }

  setEnabled() {
    this.buttonTarget.innerHTML = `
      <span class="btn-icon fa-solid fa-bell-slash fa-fw" aria-hidden="true"></span>
      <span>Disable Notifications</span>
    `;
  }

  setDisabled() {
    this.buttonTarget.innerHTML = `
      <span class="btn-icon fa-solid fa-bell fa-fw" aria-hidden="true"></span>
      <span>Notify Me</span>
    `;
  }

  setDenied() {
    this.helpTarget.innerHTML = 'You have denied permissions for notifications.';
    this.helpTarget.classList.remove('hide');
  }

  setError() {
    this.helpTarget.innerHTML = 'Something went wrong. Note that your browser might not support notifications.';
    this.helpTarget.classList.remove('hide');
  }

  setLoading() {
    this.buttonTarget.innerHTML = `
      <span class="btn-icon fa-solid fa-spinner fa-spin fa-fw" aria-hidden="true"></span>
      <span>Loading</span>
    `;
  }

  clearHelp() {
    this.helpTarget.innerHTML = '';
    this.helpTarget.classList.add('hide');
  }

  restore() {
    if (this.enabled) {
      this.setEnabled();
    } else {
      this.setDisabled();
    }
  }

  setUnsupported() {
    this.buttonTarget.setAttribute('disabled', 'disabled');

    if ('vapidPublicKey' in window) {
      this.helpTarget.innerHTML = 'Sorry, your browser does not support notifications.';
      this.helpTarget.classList.remove('hide');
    } else {
      this.helpTarget.innerHTML = 'Notifications have not been enabled by our Dude named Ben.';
      this.helpTarget.classList.remove('hide');
    }
  }
}

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding)
    .replace(/-/g, '+')
    .replace(/_/g, '/');

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }

  return outputArray;
}
