import 'bootstrap';
import jQuery from 'jquery';
import 'popper.js';

import './fontawesome';

import Clipboard from './scripts/clipboard';
import Archive from './scripts/archive';
import Player from './scripts/player';
import Token from './scripts/token';

import './app.scss';

jQuery(document).ready(() => {
  let clipboard = new Clipboard();
  let token = new Token();

  let playerUri = jQuery('[data-player]').data('player');

  if (playerUri) {
    let player = new Player(playerUri, token);
  }

  let archiveElement = jQuery('[data-archive-container]');

  if (archiveElement) {
    let archive = new Archive();
  }
});
