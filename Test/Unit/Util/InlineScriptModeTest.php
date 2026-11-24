<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Unit\Util;

use Magento\Framework\App\Cache\StateInterface as CacheState;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\TestCase;
use Yireo\CspUtilities\Config\Config;
use Yireo\CspUtilities\Util\InlineScriptMode;

class InlineScriptModeTest extends TestCase
{
    /**
     * @dataProvider getSamples
     */
    public function testMode(
        string $mode,
        bool $fullPageCache,
        bool $cacheableLayout,
        int $cachingApplication,
        bool $nonceRegenerator,
        bool $expectNonces,
        bool $expectHashes
    ): void {
        $config = $this->createMock(Config::class);
        $config->method('generateNonces')->willReturn($mode === 'nonce');
        $config->method('generateHashes')->willReturn($mode === 'hash');

        $cacheState = $this->createMock(CacheState::class);
        $cacheState->method('isEnabled')->with('full_page')->willReturn($fullPageCache);

        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')
            ->with('system/full_page_cache/caching_application')
            ->willReturn((string)$cachingApplication);

        $moduleManager = $this->createMock(ModuleManager::class);
        $moduleManager->method('isEnabled')
            ->with('Yireo_PageCacheCspNonceRegenerator')
            ->willReturn($nonceRegenerator);

        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('isCacheable')->willReturn($cacheableLayout);

        $inlineScriptMode = new InlineScriptMode($config, $cacheState, $scopeConfig, $moduleManager, $layout);

        $this->assertSame($expectNonces, $inlineScriptMode->useNonces(), 'Nonces');
        $this->assertSame($expectHashes, $inlineScriptMode->useHashes(), 'Hashes');
    }

    public static function getSamples(): array
    {
        return [
            'nonce without FPC' => ['nonce', false, true, 1, false, true, false],
            'nonce on uncacheable page' => ['nonce', true, false, 1, false, true, false],
            'nonce on cached page' => ['nonce', true, true, 1, false, false, true],
            'nonce on cached page with regenerator' => ['nonce', true, true, 1, true, true, false],
            'nonce on Varnish cached page' => ['nonce', true, true, 2, false, false, true],
            'nonce on Varnish cached page with regenerator' => ['nonce', true, true, 2, true, false, true],
            'hash without FPC' => ['hash', false, true, 1, false, false, true],
            'hash on cached page' => ['hash', true, true, 2, false, false, true],
        ];
    }
}
