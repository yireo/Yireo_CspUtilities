<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Unit\Plugin;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yireo\CspUtilities\Plugin\AddCspInlineScripts;
use Yireo\CspUtilities\Util\ReplaceInlineScripts;
use Yireo\CspUtilities\ViewModel\CspMarker;

class AddCspInlineScriptsTest extends TestCase
{
    private const HTML = '<script>var a = 1;</script>';

    private ReplaceInlineScripts&MockObject $replaceInlineScripts;
    private CspMarker $cspMarker;

    protected function setUp(): void
    {
        $this->replaceInlineScripts = $this->createMock(ReplaceInlineScripts::class);
        $this->cspMarker = new CspMarker();
    }

    public function testSkipsAllBlocksWithoutAllowlistOrFlag(): void
    {
        $this->replaceInlineScripts->expects($this->never())->method('replace');
        $this->replaceInlineScripts->expects($this->never())->method('replaceMarked');
        $block = $this->getBlock('foo');

        $plugin = $this->getPlugin([]);
        $plugin->beforeToHtml($block);

        $this->assertSame(self::HTML, $plugin->afterToHtml($block, self::HTML));
        $this->assertFalse($block->hasData('csp_marker'));
    }

    public function testSkipsBlockNotInAllowlist(): void
    {
        $this->replaceInlineScripts->expects($this->never())->method('replace');

        $this->assertSame(self::HTML, $this->getPlugin(['bar'])->afterToHtml($this->getBlock('foo'), self::HTML));
    }

    public function testReplacesAllScriptsOfAllowedBlock(): void
    {
        $this->replaceInlineScripts->expects($this->never())->method('replaceMarked');
        $this->replaceInlineScripts->expects($this->once())->method('replace')->with(self::HTML)->willReturn('replaced');

        $this->assertSame('replaced', $this->getPlugin(['foo'])->afterToHtml($this->getBlock('foo'), self::HTML));
    }

    public function testReplacesMarkedScriptsOfFlaggedBlock(): void
    {
        $this->replaceInlineScripts->expects($this->never())->method('replace');
        $this->replaceInlineScripts->expects($this->once())->method('replaceMarked')->willReturn('replaced');
        $block = $this->getBlock('foo', ['csp_utilities' => true]);

        $this->assertSame('replaced', $this->getPlugin([])->afterToHtml($block, self::HTML));
    }

    public function testSetsMarkerOnAllowedBlock(): void
    {
        $block = $this->getBlock('foo');
        $this->getPlugin(['foo'])->beforeToHtml($block);

        $this->assertSame($this->cspMarker, $block->getData('csp_marker'));
    }

    public function testKeepsExistingMarkerOfAllowedBlock(): void
    {
        $existingMarker = new CspMarker();
        $block = $this->getBlock('foo', ['csp_marker' => $existingMarker]);
        $this->getPlugin(['foo'])->beforeToHtml($block);

        $this->assertSame($existingMarker, $block->getData('csp_marker'));
    }

    public function testCastsNullHtmlToString(): void
    {
        $this->replaceInlineScripts->expects($this->never())->method('replace');

        $this->assertSame('', $this->getPlugin(['foo'])->afterToHtml($this->getBlock('foo'), null));
    }

    private function getPlugin(array $allowedBlocks): AddCspInlineScripts
    {
        return new AddCspInlineScripts($this->replaceInlineScripts, $this->cspMarker, $allowedBlocks);
    }

    private function getBlock(string $name, array $data = []): Template
    {
        $dataObject = new DataObject($data);
        $block = $this->createMock(Template::class);
        $block->method('getNameInLayout')->willReturn($name);
        $block->method('getData')->willReturnCallback(fn ($key = '') => $dataObject->getData($key));
        $block->method('hasData')->willReturnCallback(fn ($key = '') => $dataObject->hasData($key));
        $block->method('setData')->willReturnCallback(function ($key, $value = null) use ($dataObject, &$block) {
            $dataObject->setData($key, $value);
            return $block;
        });

        return $block;
    }
}
