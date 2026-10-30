import {test, HYVA_THEME} from './lib/csp';
import {defineCspUtilitiesTests} from './lib/csp-tests';

test.describe('CSP utilities (Hyva)', function () {
    defineCspUtilitiesTests(HYVA_THEME);
});
