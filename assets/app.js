import naPlayer from './services/player';
import naPlayerStorage from './services/player-storage';
import naSettings from './services/settings';
import naStorage from './services/storage';

// Include scripts
import './scripts/clipboard';
import './scripts/stimulus';
import './scripts/swup';

// Include web components
import '@octopodcasting/player';

// Include CSS
import './app.scss';

// Include images
import './images/adam-curry.jpeg';
import './images/john-c-dvorak.jpeg';
import './images/placeholder_large.jpg';
import './images/placeholder_small.jpg';
import './images/podcastindex.svg';
import './images/website-icon-32.png';
import './images/website-icon-128.png';
import './images/website-icon-180.png';
import './images/website-icon-192.png';
import './images/website-logo.svg';
import naSecurity from './services/security';

// Bootstrap application
naStorage.initialize();
naPlayer.initialize();
naPlayerStorage.initialize();
naSecurity.initialize();

naSettings.subscribe('websiteTheme', (value) => {
  if (value === 'dark') {
    document.documentElement.classList.remove('na-light');
    document.documentElement.classList.add('na-dark');
  } else if (value === 'light') {
    document.documentElement.classList.add('na-light');
    document.documentElement.classList.remove('na-dark');
  } else {
    document.documentElement.classList.remove('na-light');
    document.documentElement.classList.remove('na-dark');
  }
});

// Register service worker
(async () => {
  if ('serviceWorker' in navigator) {
    await navigator.serviceWorker.register('/service-worker.js');
  }
})();
