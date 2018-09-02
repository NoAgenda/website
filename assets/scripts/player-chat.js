import jQuery from 'jquery';
import Player from './player';

export default class PlayerChat {
  constructor() {
    this.initialized = false;
    this.loading = true;

    this.collections = {};
    this.messages = [];
    this.messagesToBeRendered = [];
    this.timestamp = 0;
    this.previousTimestamp = false;
    this.lastTimestamp = false;

    this.registerEventListeners();
  }

  registerEventListeners() {
    jQuery(document).on('click', '[data-chat-activator]', (event) => {
      event.preventDefault();

      if (this.initialized) {
        return;
      }

      this.initialized = true;

      this.reset(this.timestamp);

      // Set up styling
      this.resize();
      jQuery(window).resize(this.resize);
      jQuery('.chat-container').resize(this.resize);

      jQuery('.player-chat-activator').removeClass('d-xl-block');
    });
  }

  resize() {
    if (jQuery(window).width() >= 1200) {
      jQuery('.site-interactive-player').removeClass('container').addClass('container-fluid');
      jQuery('.player-main-col').removeClass('col-12').addClass('col-8');
      jQuery('.player-aside-col').removeClass('d-none').addClass('.d-xl-block');

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
    this.timestamp = timestamp;
    this.previousTimestamp = false;
    this.lastTimestamp = false;
    this.messagesToBeRendered = [];

    jQuery('.site-chat-message').remove();

    let collection = Math.floor(timestamp / 1000);

    if (typeof this.collections[collection] === 'undefined') {
      this.loading = true;

      jQuery('.chat-loader').removeClass('d-none');
      jQuery('.chat-responsive-container').addClass('d-none').removeClass('d-flex');
    }

    this.step(timestamp);
  }

  step(timestamp) {
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
    this.fetchMessages(timestamp);
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

  fetchMessages(timestamp) {
    let container = jQuery('[data-chat-container]');

    let collection = Math.floor(timestamp / 1000);

    if (typeof this.collections[collection] !== 'undefined') {
      timestamp = timestamp + 50;
      let futureCollection = Math.floor(timestamp / 1000);

      // Check for future messages
      if (collection !== futureCollection && typeof this.collections[futureCollection] === 'undefined') {
        this.fetchMessages(timestamp);
      }

      return;
    }

    this.collections[collection] = true;

    fetch('/chat_messages/' + container.data('episode') + '/' + collection)
      .then(response => response.json())
      .then(messages => {
        messages.map(message => this.messages.push(message));

        if (this.loading) {
          this.loading = false;

          jQuery('.chat-loader').addClass('d-none');
          jQuery('.chat-container').removeClass('d-none').addClass('d-flex');
        }
      })
    ;
  }

  postMessage(data) {
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

        this.renderMessage(response.message);
      })
    ;
  }

  renderMessage(message) {
    let container = jQuery('[data-chat-container]');
    let template = container.data('prototype');

    let html = template
      .replace('__timestamp__', this.previousTimestamp !== message[2] ? Player.formatTime(message[2]) : '')
      .replace('__username__', message[0])
      .replace('__text__', message[1])
    ;
    let element = jQuery(html);

    if (message[3] === 2) {
      element.find('.site-chat-username').css('color', 'rgba(0, 123, 255, .3)');
    }

    if (message[3] === 1 && message[0] === container.data('username')) {
      element.find('.site-chat-username').css('color', 'rgba(255, 193, 7, .3)');
    }

    container.find('> :last-child').after(element);

    this.previousTimestamp = message[2];
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
          return message[2] <= timestamp;
        })
        .sort(this.sortMessages)
        .slice(-10)
        .map(message => this.messagesToBeRendered.push(message))
      ;
    }

    this.messages
      .filter(message => {
        return message[2] > this.lastTimestamp && message[2] <= timestamp;
      })
      .map(message => this.messagesToBeRendered.push(message))
    ;

    this.messagesToBeRendered.sort(this.sortMessages);

    this.lastTimestamp = timestamp;
  }
}
