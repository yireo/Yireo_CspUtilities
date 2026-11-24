<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Util;

use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;

class CspHashGenerator
{
    public function __construct(
        private readonly DynamicCollector $dynamicCollector
    ) {
    }

    public function generate(string $script): void
    {
        $hash = base64_encode(hash('sha256', $script, true));
        $this->dynamicCollector->add(
            new FetchPolicy('script-src', false, [], [], false, false, false, [], [$hash => 'sha256'])
        );
    }
}
