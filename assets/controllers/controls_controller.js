import { Controller } from '@hotwired/stimulus';

import swup from '../scripts/swup';
import naSettings from '../services/settings';
import naSecurity from '../services/security';

export default class extends Controller {
  connect() {
    this.controls = [{
      name: 'savePlaybackPosition',
      type: 'bool',
      label: 'Remember Playback Position',
      help: 'Keep track of how far you\'ve made it into episodes of the show.',
      callback: (value) => naSettings.set('savePlaybackPosition', value),
      currentValue: () => naSettings.get('savePlaybackPosition') !== false,
    }, {
      name: 'playbackSpeed',
      type: 'choice',
      label: 'Playback Speed',
      help: 'The current playback speed for episodes.',
      choices: naSettings.playbackSpeeds,
      callback: (value) => naSettings.set('playbackSpeed', +value),
      currentValue: () => naSettings.get('playbackSpeed') ?? 100,
    }, {
      name: 'skipBackwardSeconds',
      type: 'choice',
      label: 'Skip Backward',
      help: 'The time to skip backward.',
      choices: naSettings.skipAmounts,
      callback: (value) => naSettings.set('skipBackwardSeconds', +value),
      currentValue: () => naSettings.get('skipBackwardSeconds') ?? 15,
    }, {
      name: 'skipForwardSeconds',
      type: 'choice',
      label: 'Skip Forward',
      help: 'The time to skip forward.',
      choices: naSettings.skipAmounts,
      callback: (value) => naSettings.set('skipForwardSeconds', +value),
      currentValue: () => naSettings.get('skipForwardSeconds') ?? 15,
    }, {
      name: 'websiteTheme',
      type: 'choice',
      label: 'Website Theme',
      help: 'Automatically nable the website\'s light or dark theme.',
      choices: {
        system: 'System',
        light: 'Light',
        dark: 'Dark',
      },
      callback: (value) => naSettings.set('websiteTheme', value),
      currentValue: () => naSettings.get('websiteTheme') ?? 'system',
    }, {
      name: 'account',
      type: 'button',
      label: 'Website Account',
      if: () => true,
      callback: () => {
        swup.loadPage({
          url: naSecurity.metadata.registered ? '/account' : '/login',
        });
      },
    }];

    this.render();
  }

  render() {
    this.controls.forEach((control, index) => this.renderControl(index));
  }

  renderControl(index) {
    const control = this.controls[index];

    const controlContainer = document.createElement('div');
    controlContainer.classList.add('control-group');

    if (control.type === 'bool') {
      controlContainer.innerHTML = `
        <div class="control">
          <div class="control-label">
            <label for="control_${control.name}">${control.label}</label>
            <p>${control.help}</p>
          </div>
          <div class="control-input">
            <input id="control_${control.name}" type="checkbox" ${control.currentValue() ? 'checked' : ''}>
          </div>
        </div>
      `;

      const input = controlContainer.querySelector('input');
      input.addEventListener('change', () => control.callback(input.checked));
    } else if (control.type === 'choice') {
      const options = Object.keys(control.choices).map((choiceValue) => {
        return `<option value="${choiceValue}" ${control.currentValue().toString() === choiceValue ? 'selected' : ''}>${control.choices[choiceValue]}</option>`;
      });

      controlContainer.innerHTML = `
        <div class="control">
          <div class="control-label">
            <strong>${control.label}</strong>
            <p>${control.help}</p>
          </div>
          <div class="control-input">
            <select id="control_${control.name}">
              ${options.join('')}
            </select>
          </div>
        </div>
      `;

      const select = controlContainer.querySelector('select');
      select.addEventListener('change', () => control.callback(select.value));
    } else if (control.type === 'button') {
      controlContainer.innerHTML = `
        <div class="control">
          <button id="control_${control.name}" class="btn">${control.label}</button>
        </div>
      `;

      const button = controlContainer.querySelector('button');
      button.addEventListener('click', () => control.callback());
    }

    this.element.append(controlContainer);
  }
}

/*
    <div class="control-group">
      <div class="control">
        <label for="savePlaybackPosition">
          <strong>Remember Playback Position</strong>
          Keep track of how far you've made it into an episode. For signed in users this is synchronized across
          devices and browsers.
        </label>
        <div class="input">
          <input id="savePlaybackPosition" type="checkbox">
        </div>
      </div>
      <div class="control-header">Notifications</div>
      <div class="control">
        <label for="receiveEpisodeNotification">
          <strong>Notify Me About New Episodes</strong>
          Receive a notification when a new episode of the show is released.
        </label>
        <div class="input">
          <input id="receiveEpisodeNotification" type="checkbox">
        </div>
      </div>
      <div class="control">
        <label for="receiveLiveNotification">
          <strong>Notify Me When The Livestream Starts</strong>
          Receive a notification when Adam sends out the bat signal.
        </label>
        <div class="input">
          <input id="receiveLiveNotification" type="checkbox">
        </div>
      </div>
      <div class="control-header">Offline Playback</div>
      {#
      <div class="control">
        <label for="useStorage">
          <strong>Store Data for Offline Access</strong>
          Information about episodes will be stored in your browser for easier access later.
        </label>
        <div class="input">
          <input id="useStorage" type="checkbox">
        </div>
      </div>
      <div class="control">
        <label for="saveEpisodes">
          <strong>Download Episodes</strong>
          Save the episodes you've been listening to so you can listen when you're on-the-go without wasting your data
          plan.
        </label>
        <div class="input">
          <input id="saveEpisodes" type="checkbox">
        </div>
      </div>
      #}
      <div class="control">
        <label for="saveEpisodes">
          <strong>Download Episodes</strong>
          Save the episodes you're listening to so you can listen when you're on-the-go without wasting your data
          plan.
        </label>
        <div class="input">
          <input id="saveEpisodes" type="checkbox">
        </div>
      </div>
      <div class="control">
        <label for="saveNewEpisodes">
          <strong>Always Download the Latest Episode</strong>
          Save new episodes after they're released.
        </label>
        <div class="input">
          <input id="saveNewEpisodes" type="checkbox">
        </div>
      </div>
      {#
      <div class="control">
        <label for="saveShownotes">
          <strong>Download Shownotes</strong>
          Save the shownotes of episodes you've been listening to.
        </label>
        <div class="input">
          <input id="saveShownotes" type="checkbox">
        </div>
      </div>
      <div class="control">
        <label for="saveTranscripts">
          <strong>Download Transcripts</strong>
          Save the transcript of episodes you've been listening to.
        </label>
        <div class="input">
          <input id="saveTranscripts" type="checkbox">
        </div>
      </div>
      #}
      <div class="control">
        <div>
          <strong>Storage Limit</strong>
          Specify how many episodes you want to save.
        </div>
        <div class="input">
          <select>
            <option value="3">3 episodes</option>
            <option value="5">5 episodes</option>
            <option value="10">10 episodes</option>
            <option value="33">33 episodes</option>
            <option value="all">All</option>
          </select>
        </div>
      </div>
      <div class="control-header">Experimental Zone</div>
      <div class="control">
        <div class="btn">Lightning Payments</div>
      </div>
      <div class="control-header">Danger Zone</div>
      <div class="control">
        <div class="btn">Clear Offline Data</div>
      </div>
      <div class="control">
        <div class="btn">Clear Playback Data</div>
      </div>
    </div>
 */