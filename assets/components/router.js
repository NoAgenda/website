import {getPlayer} from '../scripts/player';

const rootPath = `${window.location.protocol}//${window.location.host}`;

class RouterElement extends HTMLElement {
  connectedCallback() {
    this.updateLinks();

    window.history.replaceState(this.getCurrentState(), document.title, window.location);

    window.onpopstate = event => {
      if (!event.state) {
        return;
      }

      this.dispatchEvent(new Event('navigating'));

      document.title = event.state.title;
      this.innerHTML = event.state.contents;

      this.updateLinks();
    };
  }

  getCurrentState() {
    return {
      title: document.title,
      contents: this.innerHTML,
    };
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
        link.addEventListener('click', event => {
          if (getPlayer().playing) {
            link.setAttribute('target', '_blank');
          }
        });
      }
    });
  }

  navigate(path) {
    const errorHandler = error => {
      alert('Failed to load the page. Please contact @woodstock@noagendasocial.com if this error keeps reoccurring.'); // todo error page?

      console.error(error);

      document.querySelector('#routerFade').style.display = 'none';
    };

    document.querySelector('#routerFade').style.display = 'flex';

    fetch(path, {
      'headers': {
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
      .then(response => {
        response.json()
          .then(data => {
            this.dispatchEvent(new Event('navigating'));

            document.title = data.title;
            this.innerHTML = data.contents;

            window.history.pushState(this.getCurrentState(), data.title, response.url);

            this.updateLinks();

            document.querySelector('#routerFade').style.display = 'none';
          })
          .catch(errorHandler)
        ;
      })
      .catch(errorHandler)
    ;
  }
}

window.customElements.define('na-router', RouterElement);
