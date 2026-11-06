# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
