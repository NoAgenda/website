import * as helpers from '../player';

describe('player', () => {
  describe('helpers', () => {
    describe('serializeTime', () => {
      test('input is minutes and seconds', () => {
        expect(helpers.serializeTime('3:00')).toEqual(180);
      });
      test('input is hours minutes and seconds', () => {
        expect(helpers.serializeTime('1:3:00')).toEqual(3780);
      });
      test('input is seconds', () => {
        expect(helpers.serializeTime(':30')).toEqual(30);
      });
      test('input is seconds without colon', () => {
        expect(helpers.serializeTime('20')).toEqual(20);
      })
    });
    describe('formatTime', () => {
      describe('no hours', () => {
        test('seconds less than 10', () => {
          expect(helpers.formatTime(600)).toEqual('10:00');
        });
        test('seconds greater than 10', () => {
          expect(helpers.formatTime(633)).toEqual('10:33');
        });
      });
      describe('has hours', () => {
        test('seconds less than 10', () => {
          expect(helpers.formatTime(3600)).toEqual('1:00:00');
        });
        test('seconds greater than 10', () => {
          expect(helpers.formatTime(3633)).toEqual('1:00:33');
        });
        test('minutes less than 10', () => {
          expect(helpers.formatTime(3660)).toEqual('1:01:00');
        });
        test('minutes greater than 10', () => {
          expect(helpers.formatTime(7113)).toEqual('1:58:33');
        });
      });
    });
  });
});
