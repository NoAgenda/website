import '@fortawesome/fontawesome';
import '@fortawesome/fontawesome-pro-light';
import '@fortawesome/fontawesome-pro-regular';
import '@fortawesome/fontawesome-pro-solid';
import 'bootstrap';
import jQuery from 'jquery';
import 'popper.js';
// todo tree shaking of fa icons

import Player from './player';

import './app.scss';

jQuery(document).ready(() => {
  let playerUri = jQuery('[data-player]').data('player');

  if (playerUri) {
    let player = new Player(playerUri);
  }
});
