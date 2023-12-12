import { Controller } from '@hotwired/stimulus';

import naSettings from '../services/settings';

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
