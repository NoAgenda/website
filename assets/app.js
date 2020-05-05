import 'bootstrap';
import jQuery from 'jquery';
import 'popper.js';

import Clipboard from './scripts/clipboard';
import Archive from './scripts/archive';
import PlayerChat from './scripts/player-chat';
import PlayerCorrections from './scripts/player-corrections';
import Token from './scripts/token';

import './components/router';

import './scripts/player';
import './scripts/player-history';
import './scripts/player-transcripts';

import './app.scss';

import './images/placeholder.jpg';

jQuery(document).ready(() => {
  new Clipboard();
  const token = new Token();
  new PlayerChat();
  new PlayerCorrections(token);

  const archiveElement = jQuery('[data-archive-container]');

  if (archiveElement) {
    new Archive();
  }
});
