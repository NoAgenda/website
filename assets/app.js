import '@fortawesome/fontawesome';
import '@fortawesome/fontawesome-free-brands';
import '@fortawesome/fontawesome-pro-light';
import '@fortawesome/fontawesome-pro-regular';
import '@fortawesome/fontawesome-pro-solid';
import 'bootstrap';
import jQuery from 'jquery';
import 'popper.js';
// todo tree shaking of fa icons

import Archive from './scripts/archive';
import Player from './scripts/player';
import Token from './scripts/token';

import './app.scss';

jQuery(document).ready(() => {
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
