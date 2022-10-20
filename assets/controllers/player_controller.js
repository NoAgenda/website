import { Controller } from '@hotwired/stimulus';

import naPlayer from '../services/player';
import naSettings from '../services/settings';

export default class extends Controller {
  static targets = [
    'cover',
    'playbackSpeedButton',
    'playbackSpeedButtonText',
    'playButton',
    'playIcon',
    'skipBackwardButton',
    'skipBackwardButtonText',
    'skipForwardButton',
    'skipForwardButtonText',
    'title',
    'transcript',
    'transcriptButton',
  ];

  connect() {
    this.active = false;
    this.transcript = false;

    this.loadSubscription = naPlayer.subscribe('load', this.playerLoad);
    this.updateSubscription = naPlayer.subscribe('update', this.playerUpdate);
    this.playbackSpeedSubscription = naSettings.subscribe('playbackSpeed', this.playbackSpeedUpdate);
    this.skipBackwardSubscription = naSettings.subscribe('skipBackwardSeconds', this.skipBackwardUpdate);
    this.skipForwardSubscription = naSettings.subscribe('skipForwardSeconds', this.skipForwardUpdate);
  }

  disconnect() {
    this.loadSubscription.unsubscribe();
    this.updateSubscription.unsubscribe();
    this.playbackSpeedSubscription.unsubscribe();
    this.skipBackwardSubscription.unsubscribe();
    this.skipForwardSubscription.unsubscribe();
  }

  playerLoad = (event) => {
    this.setActive();

    if (event.mediaOptions.type === 'episode') {
      this.element.classList.add('player-playback');
    } else {
      this.element.classList.remove('player-playback');
    }

    if (this.hasTranscriptButtonTarget) {
      if (event.mediaOptions.transcript) {
        this.enableTranscript();
      } else {
        this.disableTranscript();
      }
    }

    this.titleTarget.innerHTML = event.mediaOptions.title;
    this.coverTarget.chaptersUrl = event.mediaOptions.chapters ?? null;
    this.coverTarget.imageUrl = event.mediaOptions.cover;

    this.playerUpdate(event);
  };

  playerUpdate = (event) => {
    if (event.loading) {
      this.setLoading();
    } else if (event.playing) {
      this.setPlaying();
    } else {
      this.setPaused();
    }

    this.coverTarget.currentTime = event.timestamp;
  };

  playbackSpeedUpdate = (value) => {
    value = value ?? 100;

    if (this.hasPlaybackSpeedButtonTarget) {
      this.playbackSpeedButtonTarget.setAttribute('title', `Playback Speed: Times ${naSettings.playbackSpeeds[value]}`);
      this.playbackSpeedButtonTextTarget.innerHTML = `${naSettings.playbackSpeeds[value]}&times;`;
    }
  }

  skipBackwardUpdate = (value) => {
    value = value ?? 15;

    if (this.hasSkipBackwardButtonTarget) {
      this.skipBackwardButtonTarget.setAttribute('title', `Skip Backward ${naSettings.skipAmountLabels[value]}`);
      this.skipBackwardButtonTextTarget.innerHTML = naSettings.skipAmounts[value];
    }
  }

  skipForwardUpdate = (value) => {
    value = value ?? 15;

    if (this.hasSkipForwardButtonTarget) {
      this.skipForwardButtonTarget.setAttribute('title', `Skip Forward ${naSettings.skipAmountLabels[value]}`);
      this.skipForwardButtonTextTarget.innerHTML = naSettings.skipAmounts[value];
    }
  }

  expand() {
    if (this.element.classList.contains('player-playback')) {
      document.querySelector('.player-small').classList.add('hide');
      document.querySelector('.player-large').classList.remove('player-hide');
    }
  }

  minimize() {
    document.querySelector('.player-small').classList.remove('hide');
    document.querySelector('.player-large').classList.add('player-hide');
  }

  changePlaybackSpeed() {
    const speeds = Object.keys(naSettings.playbackSpeeds);

    const currentSpeedIndex = speeds.indexOf(naSettings.get('playbackSpeed') ?? 15);
    const nextSpeed = speeds[currentSpeedIndex + 1] ?? speeds[0];

    naSettings.set('playbackSpeed', nextSpeed);
  }

  play() {
    if (naPlayer.playing) {
      naPlayer.pause();
    } else {
      naPlayer.play();
    }
  }

  resizeCover() {
    if (this.element.classList.contains('player-cover-expanded')) {
      this.element.classList.remove('player-cover-expanded');
      document.body.classList.remove('body-player-cover-expanded');
    } else {
      this.element.classList.add('player-cover-expanded');
      document.body.classList.add('body-player-cover-expanded');
    }
  }

  seekBackward() {
    naPlayer.seekRelative(0 - (naSettings.get('skipForwardSeconds') ?? 15));
  }

  seekForward() {
    naPlayer.seekRelative(naSettings.get('skipForwardSeconds') ?? 15);
  }

  enableTranscript() {
    this.transcriptButtonTarget.classList.remove('hide');

    if (this.transcript) {
      this.element.classList.add('player-transcript-active');
    }
  }

  disableTranscript() {
    this.transcriptButtonTarget.classList.add('hide');

    if (this.transcript) {
      this.element.classList.remove('player-transcript-active');
    }
  }

  toggleTranscript() {
    this.transcript = !this.transcript;

    const transcriptController = this.application.getControllerForElementAndIdentifier(this.transcriptTarget, 'player--transcript');

    if (this.transcript) {
      this.transcriptButtonTarget.classList.add('player-action-active');
      this.transcriptButtonTarget.setAttribute('title', 'Hide Live Transcript');

      this.element.classList.add('player-transcript-active');

      transcriptController.activate();
    } else {
      this.transcriptButtonTarget.classList.remove('player-action-active');
      this.transcriptButtonTarget.setAttribute('title', 'Show Live Transcript');

      this.element.classList.remove('player-transcript-active');

      transcriptController.deactivate();
    }
  }

  setActive() {
    if (!this.active) {
      this.element.classList.remove('hide');
      document.body.classList.add('body-player');

      this.active = true;
    }
  }

  setLoading() {
    this.playButtonTarget.setAttribute('title', 'Loading');
    this.playIconTarget.classList.remove('fa-play', 'fa-pause');
    this.playIconTarget.classList.add('fa-spinner', 'fa-spin');
  }

  setPlaying() {
    this.playButtonTarget.setAttribute('title', 'Pause');
    this.playIconTarget.classList.remove('fa-play', 'fa-spinner', 'fa-spin');
    this.playIconTarget.classList.add('fa-pause');
  }

  setPaused() {
    this.playButtonTarget.setAttribute('title', 'Play');
    this.playIconTarget.classList.remove('fa-pause', 'fa-spinner', 'fa-spin');
    this.playIconTarget.classList.add('fa-play');
  }
}
