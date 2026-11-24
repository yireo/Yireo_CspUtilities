<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Unit\Util;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yireo\CspUtilities\Config\Config;
use Yireo\CspUtilities\Util\CspHashGenerator;
use Yireo\CspUtilities\Util\CspNonceGenerator;
use Yireo\CspUtilities\Util\ReplaceInlineScripts;
use Yireo\CspUtilities\Util\ScriptFinder;

class ReplaceInlineScriptsTest extends TestCase
{
    private CspNonceGenerator&MockObject $cspNonceGenerator;
    private CspHashGenerator&MockObject $cspHashGenerator;
    private Config&MockObject $config;

    protected function setUp(): void
    {
        $this->cspNonceGenerator = $this->createMock(CspNonceGenerator::class);
        $this->cspHashGenerator = $this->createMock(CspHashGenerator::class);
        $this->config = $this->createMock(Config::class);
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
            $this->config
        );

        $this->assertSame('', $replaceInlineScripts->replace(''));
    }

    public function testAddsNoncesInNonceMode(): void
    {
        $this->setMode(true, false);
        $this->cspNonceGenerator->method('getNonce')->willReturn('abc123');
        $this->cspHashGenerator->expects($this->never())->method('generate');

        $html = '<div><script>var a = 1;</script><p>text</p><script type="text/javascript">var b = 2;</script></div>';
        $expected = '<div><script nonce="abc123">var a = 1;</script><p>text</p>'
            . '<script nonce="abc123" type="text/javascript">var b = 2;</script></div>';

        $this->assertSame($expected, $this->getReplaceInlineScripts()->replace($html));
    }

    public function testAddsNonceOnlyToOpeningTagNotToScriptStringsInBody(): void
    {
        $this->setMode(true, false);
        $this->cspNonceGenerator->method('getNonce')->willReturn('abc123');

        $html = '<script>document.write(\'<script src="/a.js"><\\/script>\');var t = `<script>`;</script>';
        $expected = '<script nonce="abc123">document.write(\'<script src="/a.js"><\\/script>\');var t = `<script>`;</script>';

        $this->assertSame($expected, $this->getReplaceInlineScripts()->replace($html));
    }

    public function testAddsNonceToUpperCaseScriptTag(): void
    {
        $this->setMode(true, false);
        $this->cspNonceGenerator->method('getNonce')->willReturn('abc123');

        $html = '<div><SCRIPT>var a = 1;</SCRIPT></div>';
        $expected = '<div><script nonce="abc123">var a = 1;</SCRIPT></div>';

        $this->assertSame($expected, $this->getReplaceInlineScripts()->replace($html));
    }

    public function testAddsNonceOnlyToOpeningTagWithAttributes(): void
    {
        $this->setMode(true, false);
        $this->cspNonceGenerator->method('getNonce')->willReturn('abc123');

        $html = '<script type="text/javascript" defer>var s = \'<script type="text/javascript">\';</script>';
        $expected = '<script nonce="abc123" type="text/javascript" defer>'
            . 'var s = \'<script type="text/javascript">\';</script>';

        $this->assertSame($expected, $this->getReplaceInlineScripts()->replace($html));
    }

    public function testHashesUntouchedBodyWhenAddingNonce(): void
    {
        $this->setMode(true, true);
        $this->cspNonceGenerator->method('getNonce')->willReturn('abc123');

        $body = 'document.write(\'<script src="/a.js"><\\/script>\');';
        $this->cspHashGenerator->expects($this->once())->method('generate')->with($body);

        $result = $this->getReplaceInlineScripts()->replace('<script>' . $body . '</script>');
        $this->assertSame('<script nonce="abc123">' . $body . '</script>', $result);
    }

    public function testKeepsExistingNonceWithoutDuplicating(): void
    {
        $this->setMode(true, false);
        $this->cspNonceGenerator->method('getNonce')->willReturn('abc123');

        $html = '<script nonce="other">var a = 1;</script>';
        $this->assertSame($html, $this->getReplaceInlineScripts()->replace($html));
    }

    public function testKeepsHtmlWithoutNonce(): void
    {
        $this->setMode(true, false);
        $this->cspNonceGenerator->method('getNonce')->willReturn('');

        $html = '<div><script>var a = 1;</script></div>';
        $this->assertSame($html, $this->getReplaceInlineScripts()->replace($html));
    }

    public function testAddsNonceToEachOfIdenticalScripts(): void
    {
        $this->setMode(true, false);
        $this->cspNonceGenerator->method('getNonce')->willReturn('abc123');

        $html = '<script>var a = 1;</script><script>var a = 1;</script>';
        $expected = '<script nonce="abc123">var a = 1;</script><script nonce="abc123">var a = 1;</script>';

        $this->assertSame($expected, $this->getReplaceInlineScripts()->replace($html));
    }

    /**
     * @dataProvider getHashSamples
     */
    public function testGeneratesHashOfScriptBodyInHashMode(string $html, string $body): void
    {
        $this->setMode(false, true);
        $this->cspNonceGenerator->expects($this->never())->method('getNonce');
        $this->cspHashGenerator->expects($this->once())->method('generate')->with($body);

        $this->assertSame($html, $this->getReplaceInlineScripts()->replace($html));
    }

    public static function getHashSamples(): array
    {
        return [
            ['<div><script>var a = 1;</script></div>', 'var a = 1;'],
            ['<div><script type="text/javascript" defer>var b = 2;</script></div>', 'var b = 2;'],
            ['<div><SCRIPT>var c = 3;</SCRIPT></div>', 'var c = 3;'],
            ["<div><script>\n  var d = 4;\n</script></div>", "\n  var d = 4;\n"],
        ];
    }

    public function testDoesNothingWithUnknownMode(): void
    {
        $this->setMode(false, false);
        $this->cspNonceGenerator->expects($this->never())->method('getNonce');
        $this->cspHashGenerator->expects($this->never())->method('generate');

        $html = '<div><script>var a = 1;</script></div>';
        $this->assertSame($html, $this->getReplaceInlineScripts()->replace($html));
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

    public function testReplaceScriptsWithEmptyListKeepsHtml(): void
    {
        $this->setMode(true, true);
        $this->cspNonceGenerator->expects($this->never())->method('getNonce');

        $html = '<script>var a = 1;</script>';
        $this->assertSame($html, $this->getReplaceInlineScripts()->replaceScripts($html, []));
    }

    private function setMode(bool $generateNonces, bool $generateHashes): void
    {
        $this->config->method('generateNonces')->willReturn($generateNonces);
        $this->config->method('generateHashes')->willReturn($generateHashes);
    }

    private function getReplaceInlineScripts(): ReplaceInlineScripts
    {
        return new ReplaceInlineScripts(
            new ScriptFinder(),
            $this->cspNonceGenerator,
            $this->cspHashGenerator,
            $this->config
        );
    }
}
