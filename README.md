# Magento 2 module CSP Utilities

<!-- badges.specs.start -->
![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-csp-utilities)
<!-- badges.specs.end -->


**This Magento 2 module offers CSP utilities for other modules, including various Yireo extensions.**

### Installation
Installation is usually done via other modules, but if you wanted to separately install this, here you go:
```bash
composer require yireo/magento2-csp-utilities
bin/magento module:enable Yireo_CspUtilities
```

### Configuration
- **Inline Script Mode**
  - `nonce`: Generate CSP nonces for Yireo extensions. This is the default, but it is currently not as secure in Magento, if the HTML is cached;
  - `hash`: Generate CSP hashes for Yireo extensions. This is more secure. However, with this option, your HTTP headers could grow, which could lead into webserver limits;

## Current status

<!-- badges.test.start -->
![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CspUtilities/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CspUtilities/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CspUtilities/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CspUtilities/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CspUtilities/compile.yml?label=compile)
<!-- badges.test.end -->
