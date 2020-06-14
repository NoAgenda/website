import 'bootstrap';
import jQuery from 'jquery';
import 'popper.js';

import Archive from './scripts/archive';
import PlayerChat from './scripts/player-chat';

import './components/chapter-list';
import './components/feedback-vote';
import './components/progressive-image';
import './components/router';
import './components/timestamp-input';

import './scripts/clipboard';
import './scripts/player';
import './scripts/player-history';
import './scripts/player-transcripts';
import './scripts/token';

import './app.scss';

import './images/placeholder.jpg';

jQuery(document).ready(() => {
  new PlayerChat();

  const archiveElement = jQuery('[data-archive-container]');

  if (archiveElement) {
    new Archive();
  }
});
