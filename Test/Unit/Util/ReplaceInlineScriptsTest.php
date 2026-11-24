<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Unit\Util;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yireo\CspUtilities\Util\CspHashGenerator;
use Yireo\CspUtilities\Util\CspNonceGenerator;
use Yireo\CspUtilities\Util\InlineScriptMode;
use Yireo\CspUtilities\Util\ReplaceInlineScripts;
use Yireo\CspUtilities\Util\ScriptFinder;
use Yireo\CspUtilities\ViewModel\CspMarker;

class ReplaceInlineScriptsTest extends TestCase
{
    private CspNonceGenerator&MockObject $cspNonceGenerator;
    private CspHashGenerator&MockObject $cspHashGenerator;
    private InlineScriptMode&MockObject $inlineScriptMode;
    private CspMarker $cspMarker;

    protected function setUp(): void
    {
        $this->cspNonceGenerator = $this->createMock(CspNonceGenerator::class);
        $this->cspHashGenerator = $this->createMock(CspHashGenerator::class);
        $this->inlineScriptMode = $this->createMock(InlineScriptMode::class);
        $this->cspMarker = new CspMarker();
    }

    public function testEmptyHtmlSkipsAllCollaborators(): void
    {
        $scriptFinder = $this->createMock(ScriptFinder::class);
        $scriptFinder->expects($this->never())->method('find');
        $this->cspNonceGenerator->expects($this->never())->method('getNonce');
        $this->cspHashGenerator->expects($this->never())->method('generate');

        $replaceInlineScripts = new ReplaceInlineScripts(
            $scriptFinder,
            $this->cspNonceGenerator,
            $this->cspHashGenerator,
            $this->inlineScriptMode,
            $this->cspMarker
        );

        $this->assertSame('', $replaceInlineScripts->replace(''));
    }

    public function testAddsNoncesInNonceMode(): void
    {
        $this->setMode(true, false);
        $this->cspNonceGenerator->method('getNonce')->willReturn('abc123');
        $this->cspHashGenerator->expects($this->never())->method('generate');

        $html = '<div><script ' . $this->marker() . '>var a = 1;</script><p>text</p>'
            . '<script ' . $this->marker() . ' type="text/javascript">var b = 2;</script></div>';
        $expected = '<div><script nonce="abc123">var a = 1;</script><p>text</p>'
            . '<script nonce="abc123" type="text/javascript">var b = 2;</script></div>';

        $this->assertSame($expected, $this->getReplaceInlineScripts()->replace($html));
    }

    public function testSkipsScriptsWithoutMarker(): void
    {
        $this->setMode(true, true);
        $this->cspNonceGenerator->expects($this->never())->method('getNonce');
        $this->cspHashGenerator->expects($this->never())->method('generate');

        $html = '<div><script>var a = 1;</script><script type="text/javascript">var b = 2;</script></div>';
        $this->assertSame($html, $this->getReplaceInlineScripts()->replace($html));
    }

    /**
     * @dataProvider getForgedMarkers
     */
    public function testSkipsScriptsWithForgedMarker(string $openingTag): void
    {
        $this->setMode(true, true);
        $this->cspNonceGenerator->expects($this->never())->method('getNonce');
        $this->cspHashGenerator->expects($this->never())->method('generate');

        $html = '<div>' . $openingTag . 'alert(1);</script></div>';
        $this->assertSame($html, $this->getReplaceInlineScripts()->replace($html));
    }

    public static function getForgedMarkers(): array
    {
        return [
            ['<script data-csp-utilities>'],
            ['<script data-csp-utilities="">'],
            ['<script data-csp-utilities="1">'],
            ['<script data-csp-utilities="0123456789abcdef0123456789abcdef">'],
            ['<script data-x=\'data-csp-utilities="foo"\'>'],
        ];
    }

    public function testSkipsMarkerOnNonScriptElement(): void
    {
        $this->setMode(true, true);
        $this->cspNonceGenerator->expects($this->never())->method('getNonce');
        $this->cspHashGenerator->expects($this->never())->method('generate');

        $html = '<div><script-data ' . $this->marker() . '>alert(1);</script></div>';
        $this->assertSame($html, $this->getReplaceInlineScripts()->replace($html));
    }

    public function testOnlyReplacesMarkedScripts(): void
    {
        $this->setMode(true, false);
        $this->cspNonceGenerator->method('getNonce')->willReturn('abc123');

        $html = '<script ' . $this->marker() . '>var a = 1;</script><script>var b = 2;</script>';
        $expected = '<script nonce="abc123">var a = 1;</script><script>var b = 2;</script>';

        $this->assertSame($expected, $this->getReplaceInlineScripts()->replace($html));
    }

    public function testAddsNonceOnlyToOpeningTagNotToScriptStringsInBody(): void
    {
        $this->setMode(true, false);
        $this->cspNonceGenerator->method('getNonce')->willReturn('abc123');

        $body = 'document.write(\'<script src="/a.js"><\\/script>\');var t = `<script>`;';
        $html = '<script ' . $this->marker() . '>' . $body . '</script>';

        $this->assertSame(
            '<script nonce="abc123">' . $body . '</script>',
            $this->getReplaceInlineScripts()->replace($html)
        );
    }

    public function testAddsNonceToUpperCaseScriptTag(): void
    {
        $this->setMode(true, false);
        $this->cspNonceGenerator->method('getNonce')->willReturn('abc123');

        $html = '<div><SCRIPT ' . strtoupper(CspMarker::ATTRIBUTE) . '="' . $this->cspMarker->getToken() . '">'
            . 'var a = 1;</SCRIPT></div>';
        $expected = '<div><script nonce="abc123">var a = 1;</SCRIPT></div>';

        $this->assertSame($expected, $this->getReplaceInlineScripts()->replace($html));
    }

    public function testKeepsOtherAttributesWhenRemovingMarker(): void
    {
        $this->setMode(true, false);
        $this->cspNonceGenerator->method('getNonce')->willReturn('abc123');

        $html = '<script type="text/javascript" ' . $this->marker() . ' data-x="a > b" defer>'
            . 'var s = \'<script type="text/javascript">\';</script>';
        $expected = '<script nonce="abc123" type="text/javascript" data-x="a > b" defer>'
            . 'var s = \'<script type="text/javascript">\';</script>';

        $this->assertSame($expected, $this->getReplaceInlineScripts()->replace($html));
    }

    public function testHashesUntouchedBodyWhenAddingNonce(): void
    {
        $this->setMode(true, true);
        $this->cspNonceGenerator->method('getNonce')->willReturn('abc123');

        $body = 'document.write(\'<script src="/a.js"><\\/script>\');';
        $this->cspHashGenerator->expects($this->once())->method('generate')->with($body);

        $result = $this->getReplaceInlineScripts()->replace('<script ' . $this->marker() . '>' . $body . '</script>');
        $this->assertSame('<script nonce="abc123">' . $body . '</script>', $result);
    }

    public function testKeepsExistingNonceWithoutDuplicating(): void
    {
        $this->setMode(true, false);
        $this->cspNonceGenerator->method('getNonce')->willReturn('abc123');

        $html = '<script nonce="other" ' . $this->marker() . '>var a = 1;</script>';
        $this->assertSame($html, $this->getReplaceInlineScripts()->replace($html));
    }

    /**
     * @dataProvider getInvalidNonces
     */
    public function testOnlyRemovesMarkerWithInvalidNonce(string $nonce): void
    {
        $this->setMode(true, false);
        $this->cspNonceGenerator->method('getNonce')->willReturn($nonce);

        $html = '<div><script ' . $this->marker() . '>var a = 1;</script></div>';
        $this->assertSame('<div><script>var a = 1;</script></div>', $this->getReplaceInlineScripts()->replace($html));
    }

    public static function getInvalidNonces(): array
    {
        return [
            [''],
            ['abc"><script>alert(1)</script>'],
            ['abc def'],
            ['abc==='],
        ];
    }

    public function testAddsNonceToEachOfIdenticalScripts(): void
    {
        $this->setMode(true, false);
        $this->cspNonceGenerator->method('getNonce')->willReturn('abc123');

        $script = '<script ' . $this->marker() . '>var a = 1;</script>';
        $expected = '<script nonce="abc123">var a = 1;</script><script nonce="abc123">var a = 1;</script>';

        $this->assertSame($expected, $this->getReplaceInlineScripts()->replace($script . $script));
    }

    /**
     * @dataProvider getHashSamples
     */
    public function testGeneratesHashOfScriptBodyInHashMode(
        string $openingTag,
        string $expectedOpeningTag,
        string $closingTag,
        string $body
    ): void {
        $this->setMode(false, true);
        $this->cspNonceGenerator->expects($this->never())->method('getNonce');
        $this->cspHashGenerator->expects($this->once())->method('generate')->with($body);

        $html = '<div>' . sprintf($openingTag, $this->marker()) . $body . $closingTag . '</div>';
        $expected = '<div>' . $expectedOpeningTag . $body . $closingTag . '</div>';

        $this->assertSame($expected, $this->getReplaceInlineScripts()->replace($html));
    }

    public static function getHashSamples(): array
    {
        return [
            ['<script %s>', '<script>', '</script>', 'var a = 1;'],
            ['<script type="text/javascript" %s defer>', '<script type="text/javascript" defer>', '</script>', 'var b = 2;'],
            ['<script %s>', '<script>', '</SCRIPT>', 'var c = 3;'],
            ['<script %s>', '<script>', '</script >', "\n  var d = 4;\n"],
        ];
    }

    public function testOnlyRemovesMarkerWithUnknownMode(): void
    {
        $this->setMode(false, false);
        $this->cspNonceGenerator->expects($this->never())->method('getNonce');
        $this->cspHashGenerator->expects($this->never())->method('generate');

        $html = '<div><script ' . $this->marker() . '>var a = 1;</script></div>';
        $this->assertSame('<div><script>var a = 1;</script></div>', $this->getReplaceInlineScripts()->replace($html));
    }

    public function testReplaceScriptsOnlyReplacesGivenScripts(): void
    {
        $this->setMode(true, false);
        $this->cspNonceGenerator->method('getNonce')->willReturn('abc123');

        $html = '<script>var a = 1;</script><script>var b = 2;</script>';
        $expected = '<script nonce="abc123">var a = 1;</script><script>var b = 2;</script>';

        $result = $this->getReplaceInlineScripts()->replaceScripts($html, ['<script>var a = 1;</script>']);
        $this->assertSame($expected, $result);
    }

    public function testReplaceScriptsIgnoresScriptsNotPresentInHtml(): void
    {
        $this->setMode(true, true);
        $this->cspNonceGenerator->expects($this->never())->method('getNonce');
        $this->cspHashGenerator->expects($this->never())->method('generate');

        $html = '<script>var a = 1;</script>';
        $result = $this->getReplaceInlineScripts()->replaceScripts($html, ['<script>var b = 2;</script>']);
        $this->assertSame($html, $result);
    }

    public function testReplaceScriptsIgnoresNonScriptElements(): void
    {
        $this->setMode(true, true);
        $this->cspNonceGenerator->expects($this->never())->method('getNonce');
        $this->cspHashGenerator->expects($this->never())->method('generate');

        $html = '<script-data>alert(1);</script>';
        $this->assertSame($html, $this->getReplaceInlineScripts()->replaceScripts($html, [$html]));
    }

    public function testReplaceScriptsWithEmptyListKeepsHtml(): void
    {
        $this->setMode(true, true);
        $this->cspNonceGenerator->expects($this->never())->method('getNonce');

        $html = '<script>var a = 1;</script>';
        $this->assertSame($html, $this->getReplaceInlineScripts()->replaceScripts($html, []));
    }

    private function marker(): string
    {
        return $this->cspMarker->getAttribute();
    }

    private function setMode(bool $useNonces, bool $useHashes): void
    {
        $this->inlineScriptMode->method('useNonces')->willReturn($useNonces);
        $this->inlineScriptMode->method('useHashes')->willReturn($useHashes);
    }

    private function getReplaceInlineScripts(): ReplaceInlineScripts
    {
        return new ReplaceInlineScripts(
            new ScriptFinder(),
            $this->cspNonceGenerator,
            $this->cspHashGenerator,
            $this->inlineScriptMode,
            $this->cspMarker
        );
    }
}
