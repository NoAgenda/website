import jQuery from 'jquery';

import {scrollToTranscriptLine} from '../scripts/player-transcripts';

class EpisodeSearchElement extends HTMLElement {
  constructor() {
    super();

    this.onClear = this.onClear.bind(this);
    this.onInputChange = this.onInputChange.bind(this);
  }

  connectedCallback() {
    this.episodeDetails = document.querySelector('[data-episode-contents]');
    this.input = this.querySelector('[data-episode-search]');
    this.clearButton = this.querySelector('[data-episode-search-clear]');
    this.results = this.querySelector('[data-episode-search-results]');
    this.shownotesResults = this.querySelector('[data-episode-shownotes-search-results]');
    this.transcriptResults = this.querySelector('[data-episode-transcript-search-results]');
    this.shownotesCount = this.querySelector('[data-episode-shownotes-search-count]');
    this.transcriptCount = this.querySelector('[data-episode-transcript-search-count]');

    this.input.addEventListener('input', this.onInputChange);
    this.clearButton.addEventListener('click', this.onClear);
  }

  disconnectedCallback() {
    this.input.removeEventListener('input', this.onInputChange);
  }

  onClear() {
    return new Promise(resolve => {
      this.input.value = '';

      this.onInputChange({target: {value: ''}});

      resolve();
    });
  }

  onInputChange(event) {
    this.shownotesResults.innerHTML = '';
    this.transcriptResults.innerHTML = '';

    const value = event.target.value.toLowerCase().trim();

    if (value.length < 3) {
      this.results.style.display = 'none';
      this.episodeDetails.style.display = 'flex';
      this.clearButton.style.display = 'none';

      return;
    }

    this.results.style.display = 'block';
    this.episodeDetails.style.display = 'none';
    this.clearButton.style.display = 'block';

    const shownotesRootNodes = Array.from(document.querySelectorAll('[data-episode-shownotes] > details'));
    let shownotesNodeMatches = [];

    shownotesRootNodes.forEach(shownotesNode => {
      shownotesNodeMatches = shownotesNodeMatches.concat(recursiveShownotesSearch(shownotesNode, value));
    });

    shownotesNodeMatches.forEach(node => {
      let shownoteContents = node.innerHTML;

      value.split(' ').forEach(partialValue => {
        if (!partialValue || partialValue.length < 3) {
          return;
        }

        shownoteContents = shownoteContents.replace(
          new RegExp(
            `(${inputToRegex(partialValue)})`, 'i'),
          '<span class="bg-warning">$1</span>'
        );
      });

      node.innerHTML = shownoteContents;

      this.shownotesResults.appendChild(node);
    });

    this.shownotesCount.innerHTML = shownotesNodeMatches.length.toString();

    const transcriptLines = Array.from(document.querySelectorAll('[data-episode-transcripts] .site-transcript-line'));
    const transcriptLineMatches = [];

    transcriptLines.forEach((transcriptLine, index) => {
      let lineContents = transcriptLine.querySelector('[data-transcript-contents]').innerHTML.trim();

      if (lineContents.toLowerCase().includes(value)) {
        transcriptLineMatches.push(index);
      } else if (transcriptLines[index + 1]) {
        let nextLineContents = transcriptLines[index + 1].querySelector('[data-transcript-contents]').innerHTML.trim();

        if (!nextLineContents.toLowerCase().includes(value)) {
          lineContents = `${lineContents} ${nextLineContents}`;

          if (lineContents.toLowerCase().includes(value)) {
            transcriptLineMatches.push(index);
          }
        }
      }
    });

    const ranges = calculateLineRanges(transcriptLineMatches);

    ranges.forEach(([start, end]) => {
      const originalLines = transcriptLines.slice(start, end + 1);
      const linesToRender = originalLines.map(node => node.cloneNode(true));

      linesToRender.forEach(line => {
        let transcriptContents = line.querySelector('[data-transcript-contents]').innerHTML;
        value.split(' ').forEach(partialValue => {
          if (!partialValue || partialValue.length < 3) {
            return;
          }

          transcriptContents = transcriptContents.replace(
            new RegExp(
              `(${inputToRegex(partialValue)})`, 'i'),
            '<span class="bg-warning">$1</span>'
          );
        });

        line.querySelector('[data-transcript-contents]').innerHTML = transcriptContents;
      });

      const renderSection = document.createElement('ul');
      renderSection.classList.add('site-transcript-lines');
      renderSection.classList.add('list-unstyled');

      linesToRender.forEach(line => renderSection.appendChild(line));

      const transcriptScrollLink = document.createElement('li');
      transcriptScrollLink.innerHTML = `
        <div class="media py-1">
          <div class="transcript-action text-right mr-3"></div>
          <div class="media-body text-secondary" data-transcript-contents="">
            <span class="fas fa-bars mr-1" aria-hidden="true"></span> View in full transcript
          </div>
        </div>
      `;
      transcriptScrollLink.setAttribute('role', 'button');
      transcriptScrollLink.addEventListener('click', () => {
        this.onClear()
          .then(() => {
            scrollToTranscriptLine(jQuery(originalLines[originalLines.length - 1]));
          })
        ;
      });

      renderSection.appendChild(transcriptScrollLink);

      this.transcriptResults.appendChild(renderSection);
    });

    this.transcriptCount.innerHTML = transcriptLineMatches.length.toString();
  }
}

function recursiveShownotesSearch(node, value) {
  const children = Array.from(node.children);

  let matches = [];
  let included = false;

  for (const child of children) {
    if (child.tagName === 'DETAILS') {
      matches = matches.concat(recursiveShownotesSearch(child, value));
    } else {
      if (!included && child.innerText.toLowerCase().includes(value)) {
        matches.push(node.cloneNode(true));
        included = true;
      }
    }
  }

  return matches;
}

function inputToRegex(input) {
  input = input.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&');

  return input;
}

export function calculateLineRanges(matches) {
  const parts = [...matches];
  const ranges = [];

  let startLineIndex = parts.shift();

  do {
    let start = startLineIndex - 3;
    let end = startLineIndex;

    if (start < 0) {
      start = 0;
    }

    let nextLineIndex = parts.shift();

    while (nextLineIndex - end < 9) {
      end = nextLineIndex;

      nextLineIndex = parts.shift();
    }

    end = end + 3;

    ranges.push([start, end]);

    startLineIndex = nextLineIndex;
  } while (startLineIndex);

  return ranges;
}

window.customElements.define('na-episode-search', EpisodeSearchElement);
