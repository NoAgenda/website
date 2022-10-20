import { Controller } from '@hotwired/stimulus';
import { DateTime } from 'luxon';

import { currentlyRecording, nextRecording } from '../utilities/recording_time';

export default class extends Controller {
  connect() {
    const date = DateTime.local();
    const timezoneText = date.zoneName.split('/').pop().replace('_', ' ');

    if (currentlyRecording(date, window.recordingTimes)) {
      this.element.innerHTML = '<em>The show is currently live!</em>';
    } else {
      const nextRecordingText = nextRecording(date, window.recordingTimes).toLocaleString({weekday: 'long', hour: 'numeric', minute: 'numeric'});
      this.element.innerHTML = `Next recording at: ${nextRecordingText} (${timezoneText} Time)`;
    }
  }
}
