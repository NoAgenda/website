import 'bootstrap';
import jQuery from 'jquery';
import 'popper.js';

import PlayerChat from './scripts/player-chat';

import './components/chapter-list';
import './components/feedback-vote';
import './components/progressive-image';
import './components/router';
import './components/timestamp-input';

import './scripts/clipboard';
import {initializeMastodonModal} from './scripts/mastodon';
import './scripts/player';
import './scripts/player-history';
import './scripts/player-transcripts';
import './scripts/token';

import './app.scss';

import './images/placeholder_large.jpg';
import './images/placeholder_small.jpg';

jQuery(document).ready(() => {
  initializeMastodonModal();

  new PlayerChat();
});
