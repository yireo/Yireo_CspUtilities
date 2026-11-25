# Magento 2 module CSP Utilities

<!-- badges.specs.start -->
<div class="github-specs-badges">

![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-csp-utilities)

</div>
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
  - `nonce`: Generate CSP nonces for Yireo extensions. This is the default. On pages that are cached by the Full Page Cache, a nonce would be shared between visitors, so hashes are generated instead. With the built-in Full Page Cache and the module `Yireo_PageCacheCspNonceRegenerator`, nonces are still used, because that module regenerates nonces for cached pages. With Varnish, hashes are always used for cacheable pages;
  - `hash`: Generate CSP hashes for Yireo extensions. However, with this option, your HTTP headers could grow, which could lead into webserver limits;

### Marking inline scripts
Only inline scripts that carry the marker of the current request are processed by `ReplaceInlineScripts::replaceMarked()`. The marker is a random token, generated per request, so that a script injected into the HTML (for instance, via a XSS vulnerability) never receives a valid nonce or hash. Once processed, the marker is removed again.

Add the marker to a script in your template via the ViewModel `Yireo\CspUtilities\ViewModel\CspMarker`:

```xml
<block name="example" template="Example_Module::example.phtml">
    <arguments>
        <argument name="csp_utilities" xsi:type="boolean">true</argument>
    </arguments>
</block>
```

```php
<?php /** @var \Yireo\CspUtilities\ViewModel\CspMarker $cspMarker */ ?>
<?php $cspMarker = $block->getData('csp_marker') ?>
<script data-csp-utilities="<?= $escaper->escapeHtmlAttr($cspMarker->getToken()) ?>">
    console.log('Hello world');
</script>
```

The block argument `csp_utilities` enables processing of the marked scripts in the block output and makes the marker available as block data `csp_marker`. Alternatively, inject the `CspMarker` ViewModel as a block argument yourself and call `ReplaceInlineScripts::replaceMarked()` from your own code.

The method `ReplaceInlineScripts::replace()` is deprecated: It adds a nonce or hash to all inline scripts in the HTML, including scripts that were injected into it. Blocks that are added to the `allowedBlocks` argument of `Yireo\CspUtilities\Plugin\AddCspInlineScripts` via DI are still processed with `replace()`, for backwards compatibility. Use the block argument `csp_utilities` and the marker instead.

Only inline scripts are processed: Scripts with a `src` attribute, a `nonce` attribute or a non-JavaScript `type` are skipped, so that a nonce never bypasses the host whitelist of the CSP `script-src` directive.

Blocks of which the output is cached in the `block_html` cache (via `cache_lifetime`) are not supported, because the cached output would contain the marker of an earlier request.

Callers that already determined which scripts are trusted (for instance, by comparing them with the scripts in the template file) can pass these scripts to `ReplaceInlineScripts::replaceScripts()` directly, without a marker.

## Current status

<!-- badges.test.start -->
<div class="github-pipeline-badges">

![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CspUtilities/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CspUtilities/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CspUtilities/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CspUtilities/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CspUtilities/compile.yml?label=compile)

</div>
<!-- badges.test.end -->
