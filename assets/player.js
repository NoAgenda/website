import {Howl} from 'howler';
import jQuery from 'jquery';

export default class Player {
  constructor(uri) {
    this.uri = uri;
    this.sound = new Howl({
      src: [uri],
      onload: () => {
        jQuery('[data-player-data="duration"]').text(Player.formatTime(this.sound.duration()));
      },
      onplay: () => {
        jQuery('[data-player-action="play"]').css('display', 'none');
        jQuery('[data-player-action="pause"]').css('display', 'block');

        requestAnimationFrame(this.step.bind(this));
      },
      onpause: () => {
        jQuery('[data-player-action="play"]').css('display', 'block');
        jQuery('[data-player-action="pause"]').css('display', 'none');
      },
    });

    this.registerEventListeners();
  }

  registerEventListeners() {
    jQuery('[data-player-action="pause"]').css('display', 'none');

    jQuery(document).on('click', '[data-player-action="play"]', () => {
      this.play();
    });

    jQuery(document).on('click', '[data-player-action="pause"]', () => {
      this.pause();
    });
  }

  play() {
    this.sound.play();
  }

  pause() {
    this.sound.pause();
  }

  step() {
    let time = this.sound.seek() || 0;

    let progress = (((time / this.sound.duration()) * 100) || 0) + '%';

    jQuery('[data-player-data="timer"]').text(Player.formatTime(time));
    jQuery('[data-player-data="progress"]').css('width', progress);

    // If the sound is still playing, continue stepping.
    if (this.sound.playing()) {
      requestAnimationFrame(this.step.bind(this));
    }
  }

  static formatTime(secs) {
    let minutes = Math.floor(secs / 60) || 0;
    let seconds = (secs - minutes * 60) || 0;

    return minutes + ':' + (seconds < 10 ? '0' : '') + Math.trunc(seconds);
  }
}
