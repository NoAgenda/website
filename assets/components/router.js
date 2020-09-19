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
  }

  navigate(path) {
    document.querySelector('#routerLoader').style.display = 'block';

    this.nextPath = path;

    path = this.convertPath(path);

    fetch(path, {
      'headers': {
        'X-Requested-With': 'NoAgendaRequest',
      },
    })
      .then(this.handleResponse)
      .catch(this.handleError)
    ;
  }

  submit(formElement) {
    document.querySelector('#routerLoader').style.display = 'block';

    const data = new URLSearchParams(new FormData(formElement));
    let path = formElement.getAttribute('action');

    if (!path) {
      path = document.location.toString();
    }

    this.nextPath = path;

    path = this.convertPath(path);

    fetch(path, {
      'method': 'POST',
      'headers': {
        'X-Requested-With': 'NoAgendaRequest',
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

        if (data.fragment) {
          window.location.hash = data.fragment;
        }

        this.updateForms();
        this.updateLinks();
        this.updateBullshit();

        document.querySelector('#routerLoader').style.display = 'none';

        this.dispatchEvent(new Event('navigated'));
      })
      .catch(this.handleError)
    ;
  }

  handleError(error) {
    this.querySelector('[data-content]').innerHTML = `
      <div class="container">
        <div class="my-5">
          <h3>Oops, we weren't able to load this page.</h3>
          <p>
            Make sure you're still connected to the internet. Information about your 
            request was automatically logged for review if the problem was caused by a server issue.<br>
            If the problem persists, feel free to contact our resident Dude Named Ben on
            <a href="https://twitter.com/coded_monkey" title="coded_monkey on Twitter">Twitter (@coded_monkey)</a> or
            <a href="https://noagendasocial.com/@woodstock" title="Woodstock on NA Social (Mastodon)">NA Social (@woodstock)</a>
            for help.
          </p>
          <p>
            <a href="#" class="btn-link" onclick="window.history.back();">Go back to the previous page</a>
          </p>
          <p>
            <a href="/" class="btn-link">Go to the homepage</a>
          </p>
          <p>
            <a href="#" class="btn-link" onclick="window.location.reload();">Try reloading the page (player will stop playing)</a>
          </p>
        </div>

        <na-audio-toolbar-spacer></na-audio-toolbar-spacer>
      </div>
    `;

    window.history.pushState(this.getCurrentState(), 'Failed to load the page - No Agenda', this.nextPath);

    this.updateLinks();

    console.log(error);

    document.querySelector('#routerLoader').style.display = 'none';
  }

  convertPath(path) {
    const hash = path.indexOf('#') !== -1 ? path.substr(path.indexOf('#') + 1) : null;

    if (hash) {
      path = path.substr(0, path.indexOf('#'));
      path = path.indexOf('?') !== -1 ? `${path}&_fragment=${hash}` : `${path}?_fragment=${hash}`;
    }

    path = path.indexOf('?') !== -1 ? `${path}&ajax` : `${path}?ajax`;

    return path;
  }
}

window.customElements.define('na-router', RouterElement);
