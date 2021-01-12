import * as helpers from '../episode-search';

describe('episode-search', () => {
  describe('helpers', () => {
    describe('calculateLineRanges', () => {
      test('one match', () => {
        expect(helpers.calculateLineRanges([50])).toEqual([[47, 53]]);
      });
      test('double match in small range', () => {
        expect(helpers.calculateLineRanges([50, 53])).toEqual([[47, 56]]);
      });
      test('double match', () => {
        expect(helpers.calculateLineRanges([50, 80])).toEqual([[47, 53], [77, 83]]);
      });
    });
  });
});
