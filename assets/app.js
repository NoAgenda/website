import 'bootstrap';
import jQuery from 'jquery';
import 'popper.js';

import Clipboard from './scripts/clipboard';
import Archive from './scripts/archive';
import PlayerChat from './scripts/player-chat';
import PlayerCorrections from './scripts/player-corrections';
import Token from './scripts/token';

import './scripts/player';
import './scripts/player-history';
import './scripts/player-transcripts';

import './app.scss';

jQuery(document).ready(() => {
  let clipboard = new Clipboard();
  let token = new Token();
  let playerChat = new PlayerChat();
  let playerCorrections = new PlayerCorrections(token);

  let playerUri = jQuery('[data-player]').data('player');

  if (playerUri) {
    // let player = new Player(playerUri, token);

  }

  let archiveElement = jQuery('[data-archive-container]');

  if (archiveElement) {
    let archive = new Archive();
  }
});
