import { DateTime } from 'luxon';

import { currentlyRecording, nextRecording } from '../utilities/recording_time';

const recordingTimes = [
  [4, 11],
  [7, 11],
];

const startDay = 9;

for (let day = startDay; day < (startDay + 7); day++) {
  for (let hour = 0; hour < 24; hour++) {
    const date = DateTime.utc(2022, 10, day, hour);

    test(`currentlyRecording for day ${day}, hour ${hour}`, () => {
      const isRecording = ([9, 13].includes(day) && [18, 19, 20].includes(hour));

      expect(currentlyRecording(date, recordingTimes)).toBe(isRecording);
    });

    test(`nextRecording for day ${day}, hour ${hour}`, () => {
      const test = nextRecording(date, recordingTimes).toUTC().toISO();

      if (day === 9 && hour < 18) {
        expect(test).toBe('2022-10-09T18:00:00.000Z');
      } else if (day < 13 || (day === 13 && hour < 18)) {
        expect(test).toBe('2022-10-13T18:00:00.000Z');
      } else {
        expect(test).toBe('2022-10-16T18:00:00.000Z');
      }
    });
  }
}
