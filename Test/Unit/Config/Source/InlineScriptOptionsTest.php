<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Unit\Config\Source;

use PHPUnit\Framework\TestCase;
use Yireo\CspUtilities\Config\Source\InlineScriptOptions;

class InlineScriptOptionsTest extends TestCase
{
    public function testToOptionArray(): void
    {
        $options = (new InlineScriptOptions())->toOptionArray();

        $this->assertSame(['nonce', 'hash'], array_column($options, 'value'));
    }
}
