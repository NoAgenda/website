import jQuery from 'jquery';

let lines = null;
let resetButton = null;

const activeLines = [];
let lastActiveLine = null;

jQuery(document).ready(() => {
  const player = document.getElementById('episodePlayer');

  if (!player) {
    return;
  }

  lines = jQuery('.site-transcript-line');
  resetButton = jQuery('[data-reset-transcripts]');

  const initialTimestamp = +jQuery('body').data('transcript-timestamp');
  if (initialTimestamp > 0) {
    const transcriptTab = jQuery('#transcript-tab');
    const showTabListener = () => {
      const transcriptLine = jQuery('.site-transcript-line[data-timestamp="' + initialTimestamp + '"]');

      jQuery('html,body').animate({
        scrollTop: transcriptLine.offset().top + transcriptLine.height() + 250 - jQuery(window).height(),
      });

      jQuery(window).scroll();

      transcriptTab.off('shown.bs.tab', showTabListener);
    };

    transcriptTab.on('shown.bs.tab', showTabListener);
    transcriptTab.tab('show');
  }

  player.addEventListener('audio-seek', event => stepTranscript(event.detail.timestamp));
  player.addEventListener('audio-step', event => stepTranscript(event.detail.timestamp));

  resetButton.on('click', () => {
    jQuery('html,body').animate({
      scrollTop: jQuery(lastActiveLine).offset().top + jQuery(lastActiveLine).height() + 250 - jQuery(window).height(),
    });
  });

  jQuery(window).on('scroll', () => {
    if (lastActiveLine && !lineIsOnScreen(lastActiveLine, 0)) {
      resetButton.removeClass('d-none');
    } else {
      resetButton.addClass('d-none');
    }
  })
});

function stepTranscript(timestamp) {
  for (let line of lines) {
    let lineDuration = jQuery(line).data('duration');
    let lineTimestamp = jQuery(line).data('timestamp');

    if (lineTimestamp <= timestamp) {
      if (lineDuration !== 0 && lineTimestamp + lineDuration >= timestamp) {
        activeLines.push(line);
      }

      lastActiveLine = line;
    }
  }

  const highlightedLines = jQuery('.site-transcript-line.transcript-highlight');
  let previousLineIsOnScreen = false;

  for (let line of highlightedLines) {
    if (line !== lastActiveLine && activeLines.indexOf(line) === -1) {
      jQuery(line).removeClass('transcript-highlight');
      previousLineIsOnScreen = lineIsOnScreen(line, 0);
    }
  }

  jQuery(lastActiveLine).addClass('transcript-highlight');
  activeLines.map(line => jQuery(line).addClass('transcript-highlight'));

  // Determine if a transition of transcript lines occurred and scrolls to it if it goes out of screen boundary
  if (previousLineIsOnScreen && !lineIsOnScreen(lastActiveLine, 200) && lineIsOnScreen(lastActiveLine, 0)) {
    jQuery('html,body').animate({
      scrollTop: jQuery(lastActiveLine).offset().top + jQuery(lastActiveLine).height() + 250 - jQuery(window).height(),
    });
  }

  jQuery(window).scroll();
}

function lineIsOnScreen(element, bottomOffset) {
  const elementTop = jQuery(element).offset().top;
  const elementBottom = elementTop + jQuery(element).outerHeight();

  const viewportTop = jQuery(window).scrollTop();
  const viewportBottom = viewportTop + jQuery(window).height() - bottomOffset;

  return elementTop > viewportTop && elementBottom < viewportBottom;
}
