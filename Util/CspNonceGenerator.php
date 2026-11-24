<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Util;

use Magento\Csp\Helper\CspNonceProvider;
use Magento\Framework\ObjectManagerInterface;

class CspNonceGenerator
{
    public function __construct(
        private readonly ObjectManagerInterface $objectManager
    ) {
    }

    public function getNonce(): string
    {
        // CspNonceProvider was only added in Magento 2.4.7, so it can not be injected directly
        if (false === class_exists(CspNonceProvider::class)) {
            return '';
        }

        $cspNonceProvider = $this->objectManager->get(CspNonceProvider::class);
        if (false === $cspNonceProvider instanceof CspNonceProvider) {
            return '';
        }

        return $cspNonceProvider->generateNonce();
    }
}
