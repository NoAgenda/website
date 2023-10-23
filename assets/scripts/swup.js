import SwupDebugPlugin from '@swup/debug-plugin';
import SwupFormsPlugin from '@swup/forms-plugin';
import SwupHeadPlugin from '@swup/head-plugin';
import SwupMatomoPlugin from '@swup/matomo-plugin';
import SwupProgressPlugin from '@swup/progress-plugin';
import SwupScrollPlugin from '@swup/scroll-plugin';
import Swup from 'swup';
import naSecurity from '../services/security';

class CustomFormsPlugin extends SwupFormsPlugin {
  beforeFormSubmit(event) {
    const swup = this.swup;

    swup.triggerEvent('submitForm', event);
    const form = event.target;
    if (this.isSpecialKeyPressed()) {
      this.resetSpecialKeys();
      swup.triggerEvent('openFormSubmitInNewTab', event);
      const previousFormTarget = form.getAttribute('target');
      form.setAttribute('target', '_blank');
      form.addEventListener(
        'submit',
        (event) => {
          requestAnimationFrame(() => {
            this.restorePreviousFormTarget(event.target, previousFormTarget);
          });
        },
        { once: true }
      );
      return;
    }
    this.submitForm(event);
  }
}

const plugins = [
  new CustomFormsPlugin({
    formSelector: 'form',
  }),
  new SwupHeadPlugin(),
  new SwupProgressPlugin({
    hideImmediately: false,
  }),
  new SwupScrollPlugin(),
];

if (window.naDebug) {
  plugins.push(new SwupDebugPlugin());
}

if (window._paq) {
  plugins.push(new SwupMatomoPlugin());
}

const swup = new Swup({
  animationSelector: '[class*="swup-transition"]',
  cache: false,
  linkSelector: 'a[href]:not([data-controller])',
  plugins,
});

export default swup;

window.naAuthenticating = false;

function fixDocument() {
  document.querySelectorAll('a').forEach(element => fixLink(element));

  document.querySelector('[data-login-form]')?.addEventListener('submit', () => {
    window.naAuthenticating = true;
  });

  document.querySelector('[data-logout-button]')?.addEventListener('click', () => {
    window.naAuthenticating = true;
  });
}

function fixLink(element) {
  if (window.location.host !== element.host) {
    element.setAttribute('target', '_blank');
  }
}

swup.on('pageView', () => {
  fixDocument();

  if (window.naAuthenticating) {
    naSecurity.authenticate();

    window.naAuthenticating = false;
  }
});

fixDocument();
