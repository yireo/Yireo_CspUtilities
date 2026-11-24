<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Unit\ViewModel;

use PHPUnit\Framework\TestCase;
use Yireo\CspUtilities\ViewModel\CspMarker;

class CspMarkerTest extends TestCase
{
    public function testTokenIsRandomHexadecimalString(): void
    {
        $this->assertMatchesRegularExpression('#^[a-f0-9]{32}$#', (new CspMarker())->getToken());
    }

    public function testTokenIsStablePerInstance(): void
    {
        $cspMarker = new CspMarker();

        $this->assertSame($cspMarker->getToken(), $cspMarker->getToken());
    }

    public function testTokenDiffersPerInstance(): void
    {
        $this->assertNotSame((new CspMarker())->getToken(), (new CspMarker())->getToken());
    }

    public function testGetAttribute(): void
    {
        $cspMarker = new CspMarker();

        $this->assertSame('data-csp-utilities="' . $cspMarker->getToken() . '"', $cspMarker->getAttribute());
    }
}
