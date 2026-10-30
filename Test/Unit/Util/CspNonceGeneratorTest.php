<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Unit\Util;

use Magento\Csp\Helper\CspNonceProvider;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Yireo\CspUtilities\Util\CspNonceGenerator;

class CspNonceGeneratorTest extends TestCase
{
    public function testGetNonceReturnsNonceOfProvider(): void
    {
        $cspNonceProvider = $this->createMock(CspNonceProvider::class);
        $cspNonceProvider->method('generateNonce')->willReturn('abc123');

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->with(CspNonceProvider::class)->willReturn($cspNonceProvider);

        $this->assertSame('abc123', (new CspNonceGenerator($objectManager))->getNonce());
    }

    public function testGetNonceReturnsEmptyStringWithoutMagentoCsp(): void
    {
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willThrowException(new ReflectionException('Class does not exist'));

        $this->assertSame('', (new CspNonceGenerator($objectManager))->getNonce());
    }
}
