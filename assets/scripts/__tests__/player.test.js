import "jquery"
import * as helpers from "../player" 


describe('player', () => {
  describe('helpers', () => {
    describe('serializeTime', () => {
      test('input is minutes and seconds', () => {
        expect(helpers.serializeTime('3:00')).toEqual(180);
      })
      test('input is hours minutes and seconds', () => {
        expect(helpers.serializeTime('1:3:00')).toEqual(3780);
      })
    })
  })
});