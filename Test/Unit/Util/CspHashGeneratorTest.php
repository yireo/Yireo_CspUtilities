<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Unit\Util;

use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;
use PHPUnit\Framework\TestCase;
use Yireo\CspUtilities\Util\CspHashGenerator;

class CspHashGeneratorTest extends TestCase
{
    public function testGenerateAddsScriptHash(): void
    {
        $script = "console.log('csp-hash-test');";
        $hash = base64_encode(hash('sha256', $script, true));
        $fetchPolicy = new FetchPolicy('script-src', false, [], [], false, false, false, [], [$hash => 'sha256']);

        $dynamicCollector = $this->createMock(DynamicCollector::class);
        $dynamicCollector->expects($this->once())->method('add')->with($fetchPolicy);

        (new CspHashGenerator($dynamicCollector))->generate($script);
    }
}
