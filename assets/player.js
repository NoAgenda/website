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

    jQuery(document).on('click', '[data-player-action="progress"]', (event) => {
      let distance = event.pageX - jQuery(event.currentTarget).offset().left;
      let percentage = distance / jQuery(event.currentTarget).width();

      this.seekPercentage(percentage);
    });

    jQuery(document).on('click', '[data-player-action="play-transcript"]', (event) => {
      let container = jQuery(event.currentTarget).closest('.site-transcript-line');
      let timestamp = container.data('timestamp');

      this.seekTimestamp(timestamp);
    });

    this.stepChat(0);

    jQuery(document).on('submit', 'form[name="chat_message"]', (event) => {
      event.preventDefault();

      let messageForm = jQuery('form[name="chat_message"]');
      let messageContentsInput = messageForm.find('[name="chat_message[contents]"]');
      let messagePostedAtInput = messageForm.find('[name="chat_message[postedAt]"]');

      if (messagePostedAtInput.val() < 1) {
        alert('Please start playing before posting a message.');

        return;
      }

      if (messageContentsInput.val().length < 1) {
        alert('Please enter a message before trying to post.');

        return;
      }

      let data = {};
      messageForm.serializeArray().forEach((value) => {
        let name = value.name.match(/\[([^)]+)]/)[1];
        data[name] = value.value;
      });

      let requestOptions = {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
      };
      fetch('/chat', requestOptions)
        .then(response => response.json())
        .then(response => {
          if (typeof response.status === 'undefined' || response.status === 'error') {
            alert('An error occurred while trying to post your message.');
          }

          // todo render message
        })
      ;
    });
  }

  play() {
    this.sound.play();
  }

  pause() {
    this.sound.pause();
  }

  seekPercentage(percentage) {
    if (this.sound.playing()) {
      this.sound.seek(this.sound.duration() * percentage);
    }
  }

  seekTimestamp(timestamp) {
    if (this.sound.playing()) {
      this.sound.seek(timestamp);
    }
  }

  step() {
    let timestamp = this.sound.seek() || 0;
    let progress = (((timestamp / this.sound.duration()) * 100) || 0) + '%';

    jQuery('[data-player-data="timer"]').text(Player.formatTime(timestamp));
    jQuery('[data-player-data="progress"]').css('width', progress);

    this.stepChat(timestamp);
    this.stepTranscript(timestamp);

    // If the sound is still playing, continue stepping.
    if (this.sound.playing()) {
      requestAnimationFrame(this.step.bind(this));
    }
  }

  stepChat(timestamp) {
    let messageForm = jQuery('form[name="chat_message"]');
    let messagePostedAtInput = messageForm.find('[name="chat_message[postedAt]"]');

    messagePostedAtInput.val(Math.trunc(timestamp));

    if (Math.trunc(timestamp) > 0) {
      messageForm.find('[type="submit"]').removeAttr('disabled');
    }
    else {
      messageForm.find('[type="submit"]').attr('disabled', 'disabled');
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

    let highlightedLines = jQuery('.site-transcript-line.site-transcript-highlight');

    for (let line of highlightedLines) {
      if (line !== lastActiveLine && activeLines.indexOf(line) === -1) {
        jQuery(line).removeClass('site-transcript-highlight');
      }
    }

    jQuery(lastActiveLine).addClass('site-transcript-highlight');
    activeLines.map(line => jQuery(line).addClass('site-transcript-highlight'));
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
}
