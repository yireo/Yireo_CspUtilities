# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 25 November 2026
### Added
- Blocks can opt in via the `csp_utilities` block argument: only inline scripts carrying the per-request marker of the new `CspMarker` ViewModel then receive a nonce or hash (`ReplaceInlineScripts::replaceMarked()`)
- `ReplaceInlineScripts::replaceScripts()` to add nonces or hashes to a given set of already trusted scripts
- In nonce mode, hashes are used on pages cached by the Full Page Cache, so nonces are no longer shared between visitors (unless `Yireo_PageCacheCspNonceRegenerator` regenerates them)

### Deprecated
- `ReplaceInlineScripts::replace()`, because it also trusts scripts injected into the HTML; blocks listed in `allowedBlocks` still use it

### Security
- Elements like `<script-data>` were treated as scripts and turned into executable scripts with a valid nonce (YIREO-CSP-UTILITIES-2026-001)
- External scripts with a `src` attribute received a nonce, bypassing the host whitelist of `script-src`

### Fixed
- Script markup inside an inline script body (like `document.write`) also received a nonce attribute, altering the JavaScript (#2)
- Closing tags with whitespace, quoted `>` characters in attributes and `type=module` scripts were not handled properly
- Unknown inline script modes in the configuration now fall back to nonce mode

## [1.0.2] - 06 November 2026
### Fixed
- Allow manual blocks via DI type of `allowedBlocks` arguments
- Declare Magento_Config dependency for ACL and system configuration
- Verify CSP enforcement, fresh nonces and skipped non-JS scripts for Luma and Hyva
- Cover ReplaceInlineScripts, nonce generation and config edge cases
- Add Yireo_CspUtilities::config resource used by system configuration
- Match type and nonce as whole attributes, compare type case-insensitively
- Declare module dependencies and pass trim() characters explicitly
- Read inline script mode in store scope
- Strip script tags with attributes before calculating CSP hash
- Create FetchPolicy directly instead of runtime-generated FetchPolicyFactory

## [1.0.1] - 10 March 2025
### Fixed
- Potential regex fail could cause PHP notice

## [1.0.0] - 23 August 2024
- Initial release
