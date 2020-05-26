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

    window.history.replaceState(this.getCurrentState(), document.title, window.location);

    window.onpopstate = event => {
      if (!event.state) {
        return;
      }

      this.dispatchEvent(new Event('navigating'));

      document.title = event.state.title;
      this.innerHTML = event.state.contents;
      tokenManager.authenticated = data.authenticated;

      this.updateForms();
      this.updateLinks();
    };
  }

  getCurrentState() {
    return {
      title: document.title,
      contents: this.innerHTML,
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
      })
    });
  }

  updateLinks() {
    const links = this.querySelectorAll('a');

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

          this.navigate(path);
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

        window.scrollTo(0,0);

        window.history.pushState(this.getCurrentState(), data.title, response.url);

        this.updateForms();
        this.updateLinks();

        document.querySelector('#routerFade').style.display = 'none';
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
