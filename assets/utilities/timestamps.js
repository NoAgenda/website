export function formatTimestamp(value) {
  if (value < 0) {
    return '0:00';
  }

  let hours = Math.floor(value / 60 / 60) || 0;
  let minutes = Math.floor((value - (hours * 60 * 60)) / 60) || 0;
  let seconds = (value - (minutes * 60) - (hours * 60 * 60)) || 0;

  if (hours > 0) {
    return hours + ':' + (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + Math.trunc(seconds);
  }

  return minutes + ':' + (seconds < 10 ? '0' : '') + Math.trunc(seconds);
}
