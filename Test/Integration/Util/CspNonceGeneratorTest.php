<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Integration\Util;

use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\CspUtilities\Util\CspNonceGenerator;

/**
 * @magentoAppArea frontend
 */
class CspNonceGeneratorTest extends TestCase
{
    public function testNonceIsAddedToDynamicCollector()
    {
        $nonce = ObjectManager::getInstance()->get(CspNonceGenerator::class)->getNonce();
        $this->assertNotEmpty($nonce);

        $decodedNonce = base64_decode($nonce, true);
        $this->assertNotFalse($decodedNonce, 'Nonce is not base64 encoded');

        $nonces = [];
        foreach (ObjectManager::getInstance()->get(DynamicCollector::class)->collect() as $policy) {
            if ($policy instanceof FetchPolicy && $policy->getId() === 'script-src') {
                $nonces = array_merge($nonces, $policy->getNonceValues());
            }
        }

        $this->assertContains($decodedNonce, $nonces);
    }
}
