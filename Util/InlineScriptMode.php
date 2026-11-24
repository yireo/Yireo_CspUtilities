<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Util;

use Magento\Framework\App\Cache\StateInterface as CacheState;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\View\LayoutInterface;
use Yireo\CspUtilities\Config\Config;

class InlineScriptMode
{
    private const FULL_PAGE_CACHE_TYPE = 'full_page';
    private const CACHING_APPLICATION_PATH = 'system/full_page_cache/caching_application';
    private const CACHING_APPLICATION_VARNISH = 2;
    private const NONCE_REGENERATOR_MODULE = 'Yireo_PageCacheCspNonceRegenerator';

    public function __construct(
        private readonly Config $config,
        private readonly CacheState $cacheState,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ModuleManager $moduleManager,
        private readonly LayoutInterface $layout
    ) {
    }

    public function useNonces(): bool
    {
        return $this->config->generateNonces() && false === $this->sharesCachedNonces();
    }

    public function useHashes(): bool
    {
        if ($this->config->generateHashes()) {
            return true;
        }

        return $this->config->generateNonces() && $this->sharesCachedNonces();
    }

    private function sharesCachedNonces(): bool
    {
        if (false === $this->cacheState->isEnabled(self::FULL_PAGE_CACHE_TYPE)) {
            return false;
        }

        if (false === $this->layout->isCacheable()) {
            return false;
        }

        if ($this->isVarnish()) {
            return true;
        }

        return false === $this->moduleManager->isEnabled(self::NONCE_REGENERATOR_MODULE);
    }

    private function isVarnish(): bool
    {
        return (int)$this->scopeConfig->getValue(self::CACHING_APPLICATION_PATH) === self::CACHING_APPLICATION_VARNISH;
    }
}
