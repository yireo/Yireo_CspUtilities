import {test, LUMA_THEME} from './lib/csp';
import {defineCspUtilitiesTests} from './lib/csp-tests';

test.describe('CSP utilities (Luma)', function () {
    defineCspUtilitiesTests(LUMA_THEME);
});
