<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Util;

use Magento\Csp\Helper\CspNonceProvider;
use Magento\Framework\ObjectManagerInterface;
use ReflectionException;

class CspNonceGenerator
{
    private ObjectManagerInterface $objectManager;

    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    public function getNonce(): string
    {
        try {
            $cspNonceProvider = $this->objectManager->get(CspNonceProvider::class);
        } catch (ReflectionException $reflectionException) {
            return '';
        }

        if (false === $cspNonceProvider instanceof CspNonceProvider) {
            return '';
        }

        return $cspNonceProvider->generateNonce();
    }
}
