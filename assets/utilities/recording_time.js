export function currentlyRecording(date, recordingTimes) {
  const matchingRecordingTimes = recordingTimes.map(([recordingDay, recordingHour]) => {
    const localTime = date.setZone('America/Los_Angeles');
    const startDate = localTime.set({day: localTime.day - localTime.weekday + recordingDay, hour: recordingHour, minute: 0, second: 0});
    const endDate = localTime.set({day: localTime.day - localTime.weekday + recordingDay, hour: recordingHour + 2, minute: 59, second: 59});

    return date >= startDate && date <= endDate;
  }).filter((value) => value);

  return matchingRecordingTimes.length > 0;
}

export function nextRecording(date, recordingTimes) {
  const upcomingRecordings = recordingTimes.map(([recordingDay, recordingHour]) => {
    const localTime = date.setZone('America/Los_Angeles');

    const nextRecording = localTime.set({day: localTime.day - localTime.weekday + recordingDay, hour: recordingHour, minute: 0, second: 0});

    if (date >= nextRecording) {
      const interval = {days: 7};
      return nextRecording.plus(interval);
    }

    return nextRecording;
  });

  const upcomingRecording = upcomingRecordings.reduce((a, b) => a < b ? a : b);

  return upcomingRecording.toLocal();
}
