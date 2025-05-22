<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Util;

use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicyFactory;
use Magento\Framework\ObjectManagerInterface;
use ReflectionException;

class CspHashGenerator
{
    private ObjectManagerInterface $objectManager;

    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    public function generate(string $script): void
    {
        try {
            $dynamicCollector = $this->objectManager->get(DynamicCollector::class);
            $fetchPolicyFactory = $this->objectManager->create(FetchPolicyFactory::class);
        } catch (ReflectionException $reflectionException) {
            return;
        }

        if (false === $dynamicCollector instanceof DynamicCollector) {
            return;
        }

        if (false === $fetchPolicyFactory instanceof FetchPolicyFactory) {
            return;
        }

        $hash = base64_encode(hash('sha256', $script, true));
        $fetchPolicy = $fetchPolicyFactory->create([
            'id' => 'script-src',
            'noneAllowed' => false,
            'hashValues' => [
                $hash => 'sha256',
            ],
        ]);

        $dynamicCollector->add($fetchPolicy);
    }
}
