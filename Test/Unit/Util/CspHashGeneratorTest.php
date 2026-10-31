<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Unit\Util;

use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use stdClass;
use Yireo\CspUtilities\Util\CspHashGenerator;

class CspHashGeneratorTest extends TestCase
{
    public function testGenerateAddsScriptHashWithoutFactory(): void
    {
        $script = "console.log('csp-hash-test');";
        $hash = base64_encode(hash('sha256', $script, true));
        $fetchPolicy = new FetchPolicy('script-src', false, [], [], false, false, false, [], [$hash => 'sha256']);

        $dynamicCollector = $this->createMock(DynamicCollector::class);
        $dynamicCollector->expects($this->once())->method('add')->with($fetchPolicy);

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->with(DynamicCollector::class)->willReturn($dynamicCollector);
        $objectManager->expects($this->once())
            ->method('create')
            ->with(FetchPolicy::class, [
                'id' => 'script-src',
                'noneAllowed' => false,
                'hashValues' => [$hash => 'sha256'],
            ])
            ->willReturn($fetchPolicy);

        (new CspHashGenerator($objectManager))->generate($script);
    }

    public function testGenerateSkipsWithUnexpectedObject(): void
    {
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturn(new stdClass());
        $objectManager->expects($this->never())->method('create');

        (new CspHashGenerator($objectManager))->generate('alert(1);');
    }

    public function testGenerateSkipsWithoutMagentoCsp(): void
    {
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willThrowException(new ReflectionException('Class does not exist'));
        $objectManager->expects($this->never())->method('create');

        (new CspHashGenerator($objectManager))->generate('alert(1);');
    }
}
