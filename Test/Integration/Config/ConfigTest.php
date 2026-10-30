<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Integration\Config;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\CspUtilities\Config\Config;

/**
 * @magentoAppArea frontend
 */
class ConfigTest extends TestCase
{
    public function testDefaultModeIsNonce()
    {
        $config = ObjectManager::getInstance()->get(Config::class);
        $this->assertTrue($config->generateNonces());
        $this->assertFalse($config->generateHashes());
    }

    /**
     * @magentoConfigFixture current_store csp_utilities/settings/inline_script_mode hash
     */
    public function testStoreViewValueIsUsed()
    {
        $config = ObjectManager::getInstance()->get(Config::class);
        $this->assertSame('hash', $config->getInlineScriptMode());
        $this->assertTrue($config->generateHashes());
    }
}
