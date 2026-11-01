# The Playwright tests need the fixture module Yireo_CspUtilitiesTestFixture, which renders inline scripts via ReplaceInlineScripts
case "${GITHUB_JOB:-$CI_JOB_NAME}" in playwright*)
  composer config repositories.local-source path /tmp/magento/package-source
  composer config repositories.local-source-fixture path /tmp/magento/package-source/Test/Fixture/CspUtilitiesTestFixture
  composer require -n -- yireo/magento2-csp-utilities:@dev yireo/magento2-csp-utilities-test-fixture:@dev
  ;;
esac
