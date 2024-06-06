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
  ignoreVisit: (url, { el } = {}) => (
    el?.matches('[data-no-swup], [data-controller]')
  ),
  plugins,
});

export default swup;
