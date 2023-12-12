import SwupDebugPlugin from '@swup/debug-plugin';
import SwupHeadPlugin from '@swup/head-plugin';
import SwupMatomoPlugin from '@swup/matomo-plugin';
import SwupProgressPlugin from '@swup/progress-plugin';
import SwupScrollPlugin from '@swup/scroll-plugin';
import Swup from 'swup';

const plugins = [
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
  linkSelector: 'a[href^="' + window.location.origin + '"]:not([data-no-swup]):not([data-controller]), a[href^="/"]:not([data-no-swup]):not([data-controller]), a[href^="#"]:not([data-no-swup]):not([data-controller])',
  plugins,
});

export default swup;

function fixDocument() {
  document.querySelectorAll('a').forEach(element => fixLink(element));
}

function fixLink(element) {
  if (window.location.host !== element.host) {
    element.setAttribute('target', '_blank');
  }
}

swup.on('pageView', () => {
  fixDocument();
});

fixDocument();
