#!/bin/bash
# Install the fixture module Yireo_CspUtilitiesTestFixture, needed by the Playwright tests (never in production)
set -e
MODULE_DIR=$(cd "$(dirname "$(readlink -f "$0")")" && pwd)

composer config repositories.yireo-csp-utilities-test-fixture path "$MODULE_DIR/Test/Fixture/CspUtilitiesTestFixture"
composer show yireo/magento2-csp-utilities-test-fixture >/dev/null 2>&1 \
  || composer require -n -- yireo/magento2-csp-utilities-test-fixture:@dev
bin/magento module:enable Yireo_CspUtilitiesTestFixture
