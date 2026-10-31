<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Integration;

use Magento\Framework\Acl\Builder;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class AclTest extends TestCase
{
    public function testConfigResourceExists()
    {
        $acl = ObjectManager::getInstance()->get(Builder::class)->getAcl();
        $this->assertTrue($acl->hasResource('Yireo_CspUtilities::config'));
        $this->assertTrue($acl->inheritsResource('Yireo_CspUtilities::config', 'Magento_Config::config'));
    }
}
