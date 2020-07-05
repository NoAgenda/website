import jQuery from 'jquery';

import Archive from '../scripts/archive';
import {initializeMastodonButtons} from '../scripts/mastodon';
import {getPlayer} from '../scripts/player';
import tokenManager from '../scripts/token';

const rootPath = `${window.location.protocol}//${window.location.host}`;

class RouterElement extends HTMLElement {
  constructor() {
    super();

    this.handleResponse = this.handleResponse.bind(this);
    this.handleError = this.handleError.bind(this);
  }

  connectedCallback() {
    this.updateForms();
    this.updateLinks();
    this.updateBullshit();

    window.history.replaceState(this.getCurrentState(), document.title, window.location);

    window.onpopstate = event => {
      if (!event.state) {
        return;
      }

      this.dispatchEvent(new Event('navigating'));

      document.title = event.state.title;
      this.innerHTML = event.state.contents;
      tokenManager.authenticated = event.state.authenticated;

      this.updateForms();
      this.updateLinks();
      this.updateBullshit();

      this.dispatchEvent(new Event('navigated'));
    };
  }

  getCurrentState() {
    return {
      title: document.title,
      contents: this.innerHTML,
      authenticated: tokenManager.isAuthenticated(),
    };
  }

  updateForms() {
    const forms = this.querySelectorAll('form');

    forms.forEach(form => {
      form.addEventListener('submit', event => {
        event.preventDefault();

        if (form.hasAttribute('data-token-form') && !tokenManager.isAuthenticated()) {
          tokenManager.createToken();

          return false;
        }

        this.submit(form);
      });
    });
  }

  updateLinks() {
    let links = this.querySelectorAll('a');

    if (document.querySelector('na-audio-toolbar a')) {
      links = [...links, document.querySelector('na-audio-toolbar a')];
    }

    links.forEach(link => {
      const path = link.href;
      const internal = path.startsWith(rootPath) && !path.startsWith(rootPath + '/admin');
      let tab = false;

      if (path.includes('#')) {
        const location = window.location.toString();
        const currentRoute = location.includes('#') ? location.substring(0, location.indexOf('#')) : location;
        const linkRoute = path.substring(0, path.indexOf('#'));

        if (currentRoute === linkRoute) {
          tab = true;
        }
      }

      if (internal && !tab) {
        link.addEventListener('click', event => {
          event.preventDefault();

          this.navigate(link.href);
        });
      } else if (!internal && !tab) {
        link.addEventListener('click', () => {
          if (getPlayer().playing) {
            link.setAttribute('target', '_blank');
          }
        });
      }
    });
  }

  updateBullshit() {
    // Archive
    const archiveElement = jQuery('[data-archive-container]');

    if (archiveElement) {
      this.archive = new Archive();
    }

    // Mastodon buttons
    initializeMastodonButtons();

    // Chrome notice
    const isChrome = /chrome/.test(window.navigator.userAgent.toLowerCase());

    if (isChrome && !window.localStorage.getItem('chrome-notice-hidden')) {
      document.querySelectorAll('[data-chrome-notice]').forEach(notice => {
        const alert = notice.querySelector('.alert');

        notice.classList.remove('d-none');

        jQuery(alert).on('closed.bs.alert', function() {
          window.localStorage.setItem('chrome-notice-hidden', true);
        });
      });
    }
  }

  navigate(path) {
    document.querySelector('#routerFade').style.display = 'flex';

    fetch(path, {
      'headers': {
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
      .then(this.handleResponse)
      .catch(this.handleError)
    ;
  }

  submit(formElement) {
    document.querySelector('#routerFade').style.display = 'flex';

    const data = new URLSearchParams(new FormData(formElement));

    fetch(formElement.getAttribute('action') || document.location.toString(), {
      'method': 'POST',
      'headers': {
        'X-Requested-With': 'XMLHttpRequest',
      },
      'body': data,
    })
      .then(this.handleResponse)
      .catch(this.handleError)
    ;
  }

  handleResponse(response) {
    response.json()
      .then(data => {
        this.dispatchEvent(new Event('navigating'));

        document.title = data.title;
        this.innerHTML = data.contents;
        tokenManager.authenticated = data.authenticated;

        document.querySelectorAll('[data-page-meta]').forEach(metaElement => metaElement.remove());

        if (data.meta) {
          // Adding data to the head between page loads results in rendering bugs
          // document.querySelector('head').innerHTML += data.meta;
        }

        window.scrollTo(0,0);

        window.history.pushState(this.getCurrentState(), data.title, data.path);

        this.updateForms();
        this.updateLinks();
        this.updateBullshit();

        document.querySelector('#routerFade').style.display = 'none';

        this.dispatchEvent(new Event('navigated'));
      })
      .catch(this.handleError)
    ;
  }

  handleError(error) {
    alert('Failed to load the page. Please contact @woodstock@noagendasocial.com if this error keeps reoccurring.'); // todo error page?

    console.log(error);

    document.querySelector('#routerFade').style.display = 'none';
  }
}

window.customElements.define('na-router', RouterElement);
