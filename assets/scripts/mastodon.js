'use strict';

// Stolen from https://github.com/Aly-ve/Mastodon-share-button

import $ from 'jquery';

const msbConfig = {
  openModal: function () {
    $('#mastodonModal').modal('show');
  },
  closeModal: function () {
    $('#mastodonModal').modal('hide');
  },
  addressFieldSelector: '#msb-address',
  buttonModalSelector: '#msb-share',
  memorizeFieldId: 'msb-memorize-instance',
};

const COOKIE_NAME = 'instance-address'
const URL_REGEX = /^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([\/\w .-]*)*\/?$/

function msbShareButtonAction(name, target) {
  let msbInstanceAddress = ''

  msbInstanceAddress = msbGetCookie('instance-address')
  if (msbInstanceAddress.length > 0) {
    window.open(`${msbInstanceAddress}/share?text=${name}%20${target}`, '__blank')
  }
  else {
    if (msbConfig && msbConfig.openModal && msbConfig.addressFieldSelector) {

      if (document.querySelector(msbConfig.buttonModalSelector)) {
        let bms = document.querySelector(msbConfig.buttonModalSelector)
        bms.data = { target, name }
        bms.addEventListener('click', () => msbOnShare(), false)

      }
      msbConfig.openModal(name, target)
    }
  }
}

function msbOnShare(_name, _target) {
  if (msbConfig && msbConfig.addressFieldSelector && msbConfig.buttonModalSelector) {

    let name = !!_name ? _name : document.querySelector(msbConfig.buttonModalSelector).data.name
    let target = !!_target ? _target : document.querySelector(msbConfig.buttonModalSelector).data.target
    let msbInstanceAddress = document.querySelector(`${msbConfig.addressFieldSelector}`).value

    if (!msbInstanceAddress) {
      msbInstanceAddress = 'https://noagendasocial.com';
    }

    if (msbInstanceAddress.match(URL_REGEX)) {
      if (msbConfig.memorizeFieldId) {
        let msbMemorizeIsChecked = document.querySelector(`#${msbConfig.memorizeFieldId}`).checked
        if (msbConfig.memorizeFieldId && !msbGetCookie(COOKIE_NAME).length > 0 && msbMemorizeIsChecked) {
          msbSetCookie(COOKIE_NAME, msbInstanceAddress, 7);
        }
      }

      window.open(`${msbInstanceAddress}/share?text=${name}%20${target}`, '__blank')
      if (msbConfig && msbConfig.openModal && msbConfig.closeModal) {
        msbConfig.closeModal()
      }
    }
  }
}

function msbGetCookie(cname) {
  var name = cname + '=';
  var ca = document.cookie.split(';');
  for(var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return '';
}

function msbSetCookie(name, value, days) {
  let d = new Date()
  d.setTime(d.getTime() + days*86400000)
  let expires = 'expires=' + d.toUTCString()
  document.cookie = `${name}=${value}; ${expires}; path=/`
}

export const initializeMastodonModal = () => {
  $('#mastodonModal').on('shown.bs.modal', function() {
    $('#msb-address').focus();
  });
}

export const initializeMastodonButtons = () => {
  let msbButtons = document.querySelectorAll('.mastodon-share-button')

  for(let i = 0; i < msbButtons.length; i++) {
    (function(j) {
      let msbName = msbButtons[j].dataset.name

      // Replace hashtab by html code
      msbName = msbName.replace(/#/g, '%23')

      /**
       * Set the listener in each button
       */
      msbButtons[j].addEventListener('click', () => { msbShareButtonAction(msbName, msbButtons[j].dataset.target) }, true)

    })(i)
  }
}

function msbI18n() {
  let language = navigator.language || navigator.userLanguage
  let publish = {
    'ar': 'بوّق',
    'bg': 'Раздумай',
    'cs': 'Tootnout',
    'de': 'Tröt',
    'eo': 'Hué',
    'es': 'Tootear',
    'eu': 'Tut',
    'fa': 'بوق',
    'fi': 'Tuuttaa',
    'fr': 'Pouet',
    'gl': 'ללחוש',
    'he': 'ללחוש',
    'hu': 'Tülk',
    'hy': 'Թթել',
    'io': 'Siflar',
    'ja': 'トゥート',
    'ko': '툿',
    'no': 'Tut',
    'oc': 'Tut',
    'pl': 'Wyślij',
    'pt-BR': 'Publicar',
    'pt': 'Publicar',
    'ru': 'Трубить',
    'sr-Latn': 'Tutni',
    'sr': 'Тутни',
    'uk': 'Дмухнути',
    'zh-CN': '嘟嘟',
    'zh-HK': '發文',
    'zh-TW': '貼掉',
    'default': 'Toot'
  }

  let text = null
  try {
    text = publish[language]
  }
  catch (error) {
    text = publish.default
  }

  return text
}
