<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Integration\Util;

use Composer\Autoload\ClassLoader;
use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Csp\Model\Policy\FetchPolicyFactory;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\CspUtilities\Util\CspHashGenerator;

/**
 * @magentoAppArea frontend
 */
class CspHashGeneratorTest extends TestCase
{
    /**
     * @see https://github.com/yireo/Yireo_CspUtilities/issues/1
     */
    public function testDoesNotGenerateClassesAtRuntime()
    {
        if (class_exists(FetchPolicyFactory::class, false)) {
            $this->markTestSkipped('FetchPolicyFactory was already loaded by another test');
        }

        ObjectManager::getInstance()->get(CspHashGenerator::class)->generate("console.log('runtime-generation');");

        $this->assertFalse(
            class_exists(FetchPolicyFactory::class, false),
            'FetchPolicyFactory is not compiled by setup:di:compile and must not be generated at runtime'
        );

        foreach (ClassLoader::getRegisteredLoaders() as $classLoader) {
            $classMap = $classLoader->getClassMap();
            $this->assertFalse(
                array_key_exists(FetchPolicy::class, $classMap) && $classMap[FetchPolicy::class] === null,
                'The classmap entry of FetchPolicy was removed by runtime code generation'
            );
        }
    }

    public function testAddsHashToDynamicCollector()
    {
        $script = "console.log('csp-hash-integration-test');";
        ObjectManager::getInstance()->get(CspHashGenerator::class)->generate($script);

        $hash = base64_encode(hash('sha256', $script, true));
        $this->assertContains($hash, $this->getScriptSrcHashes());
    }

    /**
     * @return string[]
     */
    private function getScriptSrcHashes(): array
    {
        $hashes = [];
        foreach (ObjectManager::getInstance()->get(DynamicCollector::class)->collect() as $policy) {
            if ($policy instanceof FetchPolicy && $policy->getId() === 'script-src') {
                $hashes = array_merge($hashes, array_keys($policy->getHashes()));
            }
        }

        return $hashes;
    }
}
