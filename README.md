# Magento 2 module CSP Utilities

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
