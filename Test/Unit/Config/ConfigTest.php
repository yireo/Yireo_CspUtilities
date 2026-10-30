<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Unit\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;
use Yireo\CspUtilities\Config\Config;

class ConfigTest extends TestCase
{
    public function testDefaultsToNonceMode(): void
    {
        $config = new Config($this->getScopeConfig(null));

        $this->assertSame('nonce', $config->getInlineScriptMode());
        $this->assertTrue($config->generateNonces());
        $this->assertFalse($config->generateHashes());
    }

    public function testHashMode(): void
    {
        $config = new Config($this->getScopeConfig('hash'));

        $this->assertSame('hash', $config->getInlineScriptMode());
        $this->assertFalse($config->generateNonces());
        $this->assertTrue($config->generateHashes());
    }

    public function testReadsValueInStoreScope(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('csp_utilities/settings/inline_script_mode', ScopeInterface::SCOPE_STORE)
            ->willReturn('hash');

        $this->assertTrue((new Config($scopeConfig))->generateHashes());
    }

    private function getScopeConfig(?string $value): ScopeConfigInterface
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn($value);

        return $scopeConfig;
    }
}
