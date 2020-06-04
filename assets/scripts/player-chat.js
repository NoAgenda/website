import jQuery from 'jquery';

import {formatTime, getPlayer} from './player';

export default class PlayerChat {
  constructor() {
    this.initialized = false;
    this.loading = true;

    this.collection = false;
    this.messages = [];
    this.messagesToBeRendered = [];
    this.timestamp = 0;
    this.previousTimestamp = false;
    this.lastTimestamp = false;
    this.episodeCode = false;

    this.onActivateChat = this.onActivateChat.bind(this);
    this.onAudioSeek = this.onAudioSeek.bind(this);
    this.onAudioStep = this.onAudioStep.bind(this);

    jQuery(document).on('click', '[data-chat-activator]', this.onActivateChat);
    jQuery('na-router').on('navigating', () => {
      if (this.initialized) {
        getPlayer().removeEventListener('audio-seek', this.onAudioSeek);
        getPlayer().removeEventListener('audio-step', this.onAudioStep);

        this.initialized = false;
        this.loading = true;

        this.collection = false;
        this.messages = [];
        this.messagesToBeRendered = [];
        this.timestamp = 0;
        this.previousTimestamp = false;
        this.lastTimestamp = false;
        this.episodeCode = false;
      }
    })
  }

  onActivateChat(event) {
    event.preventDefault();

    if (this.initialized) {
      return;
    }

    this.initialized = true;

    getPlayer().addEventListener('audio-seek', this.onAudioSeek);
    getPlayer().addEventListener('audio-step', this.onAudioStep);

    this.loading = true;

    jQuery('.chat-loader').removeClass('d-none');
    jQuery('.chat-responsive-container').addClass('d-none').removeClass('d-flex');

    this.fetchMessages();
    this.reset(this.timestamp);

    // Set up styling
    this.resize();
    jQuery(window).resize(this.resize);
    jQuery('.chat-container').resize(this.resize);

    jQuery('.player-chat-activator').removeClass('d-xl-block');
  }

  onAudioSeek(event) {
    this.reset(event.detail.timestamp);
  }

  onAudioStep(event) {
    this.step(event.detail.timestamp);
  }

  resize() {
    if (jQuery(window).width() >= 1200) {
      jQuery('.site-interactive-player').removeClass('container').addClass('container-fluid');
      jQuery('.player-main-col').removeClass('col-12').addClass('col-8');
      jQuery('.player-aside-col').removeClass('d-none').addClass('d-xl-block');

      let chatTab = jQuery('#chat-tabcontent');

      if (chatTab.hasClass('active')) {
        jQuery('.player-tabs li:first-child div').tab('show');
      }
    }
    else {
      jQuery('.site-interactive-player').addClass('container').removeClass('container-fluid');
      jQuery('.player-main-col').addClass('col-12').removeClass('col-8');
      jQuery('.player-aside-col').addClass('d-none');

      let responsiveContainer = jQuery('.chat-responsive-container');
      let activeTab = responsiveContainer.closest('.flex-grow-1').find('> .tab-pane.active');

      activeTab.removeClass('active').removeClass('show');

      // responsiveContainer.css('height', 'calc(' + responsiveContainer.closest('.flex-grow-1').height() + 'px - 1.5rem)');
      responsiveContainer.css('height', responsiveContainer.closest('.flex-grow-1').height() + 'px');

      activeTab.addClass('active').addClass('show');
    }
  }

  reset(timestamp) {
    const source = jQuery('#episodeSource');

    if (!source.length || source[0].hash !== getPlayer().hash) {
      return;
    }

    this.timestamp = timestamp;
    this.previousTimestamp = false;
    this.lastTimestamp = false;
    this.messagesToBeRendered = [];

    jQuery('.site-chat-message').remove();

    this.step(timestamp);
  }

  step(timestamp) {
    const source = jQuery('#episodeSource');

    if (!source.length || source[0].hash !== getPlayer().hash) {
      return;
    }

    this.timestamp = timestamp;

    if (!this.initialized) {
      return;
    }

    jQuery('.site-chat-messages').each((index, element) => {
      let messages = jQuery(element).find('.site-chat-message');
      if (messages.length >= 500) {
        messages.slice(0, messages.length - 500).remove();
      }
    });

    // Grab current scroll position
    let messageViewportContainer = jQuery(window).width() >= 1200 ? jQuery('.player-aside-col .chat-container.oy-scroll') : jQuery('.chat-responsive-container .oy-scroll') ;
    let maxScrollTop = messageViewportContainer.get(0).scrollHeight - messageViewportContainer.height();

    // Render messages
    this.updateMessagesToBeRendered(timestamp);

    if (this.messagesToBeRendered.length === 0) {
      return;
    }

    this.messagesToBeRendered.map(message => this.renderMessage(message));

    this.messagesToBeRendered = [];

    // Update scroll position
    let newScrollTop = messageViewportContainer.get(0).scrollHeight - messageViewportContainer.height();

    if (newScrollTop !== maxScrollTop && messageViewportContainer.get(0).scrollTop > maxScrollTop - 16) {
      messageViewportContainer.get(0).scrollTop = messageViewportContainer.get(0).scrollHeight;
    }
  }

  fetchMessages() {
    let container = jQuery('[data-chat-container]');

    this.episodeCode = container.data('episode');

    fetch('/listen/' + this.episodeCode + '/chat')
      .then(response => response.json())
      .then(messages => {
        messages.map(message => this.messages.push(message));

        this.loading = false;

        jQuery('.chat-loader').addClass('d-none');
        jQuery('.chat-container').removeClass('d-none').addClass('d-flex');
      })
    ;
  }

  renderMessage(message) {
    let container = jQuery('[data-chat-container]');
    let template = container.data('prototype');
    let html = null;

    if (message.contents.substring(0, 7) === 'ACTION ') {
       html = template
        .replace('__timestamp__', this.previousTimestamp !== message.timestamp ? formatTime(message.timestamp) : '')
        .replace('__username__', '')
        .replace('__text__', message.username + ' ' + message.contents.substring(7))
      ;
    } else {
      html = template
        .replace('__timestamp__', this.previousTimestamp !== message.timestamp ? formatTime(message.timestamp) : '')
        .replace('__username__', message.username)
        .replace('__text__', message.contents)
      ;
    }

    html = replaceUrls(html);
    let element = jQuery(html);

    container.find('> :last-child').after(element);

    this.previousTimestamp = message.timestamp;
  }

  sortMessages(a, b) {
    // Sort by timestamp
    if (a[2] > b[2]) {
      return 1;
    }
    else if (a[2] < b[2]) {
      return -1;
    }

    // Sort by source
    if (a[3] > b[3]) {
      return 1;
    }
    else if (a[3] < b[3]) {
      return -1;
    }

    return 0;
  }

  updateMessagesToBeRendered(timestamp) {
    if (this.loading || this.messagesToBeRendered.length > 0) {
      return;
    }

    if (this.lastTimestamp === false) {
      this.lastTimestamp = timestamp;

      this.messages
        .filter(message => {
          return message.timestamp <= timestamp;
        })
        .sort(this.sortMessages)
        .slice(-10)
        .map(message => this.messagesToBeRendered.push(message))
      ;
    }

    this.messages
      .filter(message => {
        return message.timestamp > this.lastTimestamp && message.timestamp <= timestamp;
      })
      .map(message => this.messagesToBeRendered.push(message))
    ;

    this.messagesToBeRendered.sort(this.sortMessages);

    this.lastTimestamp = timestamp;
  }
}

function replaceUrls(inputText) {
  let replacedText, replacePattern1, replacePattern2;

  //URLs starting with http://, https://, or ftp://
  replacePattern1 = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
  replacedText = inputText.replace(replacePattern1, '<a href="$1" target="_blank">$1</a>');

  //URLs starting with "www." (without // before it, or it'd re-link the ones done above).
  replacePattern2 = /(^|[^\/])(www\.[\S]+(\b|$))/gim;
  replacedText = replacedText.replace(replacePattern2, '$1<a href="http://$2" rel="nofollow" target="_blank">$2</a>');

  return replacedText;
}
