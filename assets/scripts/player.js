import {Howl} from 'howler';
import jQuery from 'jquery';
import PlayerChat from './player-chat';
import PlayerCorrections from './player-corrections';

export default class Player {
  constructor(uri, token) {
    this.timestamp = jQuery('[data-player]').data('player-timestamp') || 0;
    this.uri = uri;

    this.token = token;
    this.chat = new PlayerChat();
    this.corrections = new PlayerCorrections(this, token);
    this.sound = new Howl({
      src: [uri],
      onload: () => {
        jQuery('[data-player-data="duration"]').text(Player.formatTime(this.sound.duration()));

        this.stepInterface(this.timestamp);
        this.stepParts(this.timestamp);
        this.stepTranscript(this.timestamp);
      },
      onplay: () => {
        jQuery('[data-player-action="play"]').css('display', 'none');
        jQuery('[data-player-action="pause"]').css('display', 'inherit');

        requestAnimationFrame(this.step.bind(this));
      },
      onpause: () => {
        jQuery('[data-player-action="play"]').css('display', 'inherit');
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

    jQuery(document).on('click', '[data-player-action="forward"]', (event) => {
      let amount = jQuery(event.currentTarget).data('amount');

      this.seekTimestamp(this.timestamp + amount);
    });

    jQuery(document).on('click', '[data-player-action="rewind"]', (event) => {
      let amount = jQuery(event.currentTarget).data('amount');

      this.seekTimestamp(this.timestamp - amount);
    });

    jQuery(document).on('click', '[data-player-action="progress"]', (event) => {
      let distance = event.pageX - jQuery(event.currentTarget).offset().left;
      let percentage = distance / jQuery(event.currentTarget).width();

      this.seekPercentage(percentage);
    });

    jQuery(document).on('click', '[data-player-action="play-timestamp"]', (event) => {
      event.stopPropagation();

      let timestamp = jQuery(event.currentTarget).data('timestamp');

      this.seekTimestamp(timestamp);

      if (!this.sound.playing()) {
        this.play();
      }
    });

    jQuery(document).on('click', '.site-episode-part', (event) => {
      let collapse = jQuery(event.currentTarget).find('.collapse');

      if (!collapse.hasClass('show')) {
        collapse.collapse('show');
      }
    });

    jQuery(document).on('show.bs.collapse', '.site-episode-parts .collapse', () => {
      jQuery('.site-episode-parts .collapse.show').collapse('hide');
    });
  }

  play() {
    this.sound.play();

    let timestamp = this.sound.seek() || 0;

    if (timestamp !== this.timestamp) {
      this.sound.seek(this.timestamp);
    }
  }

  pause() {
    this.sound.pause();
  }

  seekPercentage(percentage) {
    let duration = this.sound.duration() || 0;
    let timestamp = percentage * duration;

    if (this.sound.playing()) {
      this.sound.seek(timestamp);
    }
    else {
      this.timestamp = timestamp;

      this.stepInterface(timestamp);
      this.stepParts(timestamp);
      this.stepTranscript(timestamp);
    }

    this.chat.reset(timestamp);
  }

  seekTimestamp(timestamp) {
    if (this.sound.playing()) {
      this.sound.seek(timestamp);
    }
    else {
      this.timestamp = timestamp;

      this.stepInterface(timestamp);
      this.stepParts(timestamp);
      this.stepTranscript(timestamp);
    }

    this.chat.reset(timestamp);
  }

  step() {
    let timestamp = this.sound.seek() || 0;

    this.timestamp = timestamp;

    this.stepInterface(timestamp);
    this.stepParts(timestamp);
    this.stepTranscript(timestamp);
    this.chat.step(timestamp);

    // If the sound is still playing, continue stepping.
    if (this.sound.playing()) {
      requestAnimationFrame(this.step.bind(this));
    }
  }

  stepInterface(timestamp) {
    let duration = this.sound.duration() || 0;
    let progress = (((timestamp / duration) * 100) || 0) + '%';

    jQuery('[data-player-data="timer"]').text(Player.formatTime(timestamp));
    jQuery('[data-player-data="progress"]').css('width', progress);

    jQuery('[data-player-data="timer-attribute"]').each((index, element) => {
      element = jQuery(element);
      let attribute = element.data('player-attribute');

      if (typeof element.data('original-' + attribute) === 'undefined') {
        element.data('original-' + attribute, element.data(attribute) || element.attr(attribute));
      }

      if (!element.data('original-' + attribute)) {
        return;
      }

      let original = element.data('original-' + attribute);
      element.attr(attribute, original.replace('t=0:00', 't=' + Player.formatTime(timestamp)));
    });
  }

  stepParts(timestamp) {
    let parts = jQuery('.site-episode-part');

    let lastActivePart = null;

    for (let part of parts) {
      let partTimestamp = jQuery(part).data('timestamp');

      if (partTimestamp <= timestamp) {
        lastActivePart = jQuery(part);
      }
    }

    parts.removeClass('part-highlight');

    if (lastActivePart) {
      lastActivePart.addClass('part-highlight');

      if (lastActivePart.data('name')) {
        jQuery('[data-player-data="chapter-name"]').text('Now playing: ' + lastActivePart.data('name'));
      }
      else {
        jQuery('[data-player-data="chapter-name"]').text('');
      }
    }
  }

  stepTranscript(timestamp) {
    let lines = jQuery('.site-transcript-line');

    let lastActiveLine = null;
    let activeLines = [];

    for (let line of lines) {
      let lineDuration = jQuery(line).data('duration');
      let lineTimestamp = jQuery(line).data('timestamp');

      if (lineTimestamp <= timestamp) {
        if (lineDuration !== 0 && lineTimestamp + lineDuration >= timestamp) {
          activeLines.push(line);
        }

        lastActiveLine = line;
      }
    }

    let highlightedLines = jQuery('.site-transcript-line.transcript-highlight');
    let previousLineIsOnScreen = false;

    for (let line of highlightedLines) {
      if (line !== lastActiveLine && activeLines.indexOf(line) === -1) {
        jQuery(line).removeClass('transcript-highlight');
        previousLineIsOnScreen = Player.lineIsOnScreen(line, 0);
      }
    }

    jQuery(lastActiveLine).addClass('transcript-highlight');
    activeLines.map(line => jQuery(line).addClass('transcript-highlight'));

    // Determine if a transition of transcript lines occurred and scrolls to it if it goes out of screen boundary
    if (previousLineIsOnScreen && !Player.lineIsOnScreen(lastActiveLine, 200) && Player.lineIsOnScreen(lastActiveLine, 0)) {
      jQuery('html,body').animate({
        scrollTop: jQuery(lastActiveLine).offset().top + jQuery(lastActiveLine).height() + 250 - jQuery(window).height(),
      });
    }
  }

  static formatTime(value) {
    let hours = Math.floor(value / 60 / 60) || 0;
    let minutes = Math.floor((value - (hours * 60 * 60)) / 60) || 0;
    let seconds = (value - (minutes * 60) - (hours * 60 * 60)) || 0;

    if (hours > 0) {
      return hours + ':' + (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + Math.trunc(seconds);
    }

    return minutes + ':' + (seconds < 10 ? '0' : '') + Math.trunc(seconds);
  }

  static serializeTime(value) {
    let values = value.split(':');
    let result = false;

    if (values.length > 2) {
      result = (+values[0]) * 60 * 60 + (+values[1]) * 60 + (+values[2]);
    }
    else if (values.length === 2) {
      result = (+values[0]) * 60 + (+values[1]);
    }

    return result;
  }

  static lineIsOnScreen(element, bottomOffset) {
    let elementTop = jQuery(element).offset().top;
    let elementBottom = elementTop + jQuery(element).outerHeight();

    let viewportTop = jQuery(window).scrollTop();
    let viewportBottom = viewportTop + jQuery(window).height() - bottomOffset;

    return elementTop > viewportTop && elementBottom < viewportBottom;
  };
}
