<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Unit\Util;

use PHPUnit\Framework\TestCase;
use Yireo\CspUtilities\Util\ScriptFinder;

class ScriptFinderTest extends TestCase
{
    /**
     * @param $html
     * @param $scriptCount
     * @return void
     * @dataProvider getHtmlSamples
     */
    public function testFind($html, $scriptCount)
    {
        $scriptFinder = new ScriptFinder();
        $this->assertEquals($scriptCount, count($scriptFinder->find($html)), $html);
    }

    public static function getHtmlSamples(): array
    {
        return [
            ["<div>\n</div>", 0],
            ["<div>\n<script>\nalert(true);</script>\n</div>", 1],
            ["<div>\n<script></script>\n</div>", 0],
            ["<div>\n<script nonce='foobar'>\nalert(true);</script>\n</div>", 0],
            ['<div><script type="text/javascript">foobar</script></div>', 1],
            ['<div><script type="text/javascript" nonce="foobar">foobar</script></div>', 0],
            ['<div><script type="application/javascript">foobar</script></div>', 1],
            ['<div><script type="text/x-magento-init">foobar</script></div>', 0],
            ['<div><script>test1</script><script>test2</script></div>', 2],
            ['<div><script defer>foobar</script></div>', 1],
            ['<div><SCRIPT>foobar</SCRIPT></div>', 1],
            ['<div><script type="module">foobar</script></div>', 1],
            ['<div><script type="text/x-magento-template">foobar</script></div>', 0],
            ["<div><script type='text/javascript'>foobar</script></div>", 1],
            ["<div><script>\n \t</script></div>", 0],
            ['<div><script src="https://example.com/foo.js"></script></div>', 0],
            ["<div><script\n  type=\"text/javascript\"\n  defer>foobar</script></div>", 1],
            ["  \n\t ", 0],
            ['', 0],
            ['<div><script data-type="foobar">foobar</script></div>', 1],
            ['<div><script type="TEXT/JavaScript">foobar</script></div>', 1],
            ['<div><script type=text/javascript defer>foobar</script></div>', 1],
            ['<div><script type=text/x-magento-init>foobar</script></div>', 0],
            ['<div><script data-nonce="foobar">foobar</script></div>', 1],
            ['<div><script data-foo="x" nonce="foobar">foobar</script></div>', 0],
            ['<div><script>document.write(\'<script src="/a.js"><\\/script>\');</script></div>', 1],
            ['<div><script>var t = `<script>`;</script></div>', 1],
            ['<div><script src="https://evil.example/x.js">;</script></div>', 0],
            ['<div><script type="text/javascript" src="/x.js">foobar</script></div>', 0],
            ['<div><script SRC=/x.js>foobar</script></div>', 0],
            ["<div><script\n src='/x.js'>foobar</script></div>", 0],
            ['<div><script data-src="/x.js">foobar</script></div>', 1],
            ['<div><script-data>foobar</script></div>', 0],
            ['<div><scripts>foobar</script></div>', 0],
            ['<div><script-data>foo</script><script>bar</script></div>', 1],
            ['<div><script>foo</script ><p>text</p><script>bar</script></div>', 2],
            ["<div><script>foo</script\n><script>bar</SCRIPT ></div>", 2],
            ['<div><script data-x=">" type="text/x-magento-template">foobar</script></div>', 0],
            ['<div><script data-x="a > b" type="text/javascript">foobar</script></div>', 1],
            ['<div><script data-x="type=text/html">foobar</script></div>', 1],
            ['<div><script data-x="nonce=abc">foobar</script></div>', 1],
            ['<div><script data-x=\'src=/x.js\'>foobar</script></div>', 1],
            ['<div><script type=" text/javascript ">foobar</script></div>', 1],
            ['<div><script type="">foobar</script></div>', 1],
            ['<div><script type>foobar</script></div>', 1],
            ['<div><script NONCE>foobar</script></div>', 0],
            ['<div><script/>foobar</script></div>', 1],
        ];
    }

    public function testFindReturnsScriptWithScriptMarkupInBodyAsSingleMatch(): void
    {
        $script = '<script>document.write(\'<script src="/a.js"><\\/script>\');var t = `<script>`;</script>';

        $this->assertSame([$script], (new ScriptFinder())->find('<div>' . $script . '</div>'));
    }

    public function testFindReturnsFullScriptTags(): void
    {
        $html = "<div><script>var a = 1;</script>\n<script type=\"text/x-magento-init\">{}</script>"
            . "\n<script defer>\nvar b = 2;\n</script></div>";

        $this->assertSame(
            ['<script>var a = 1;</script>', "<script defer>\nvar b = 2;\n</script>"],
            (new ScriptFinder())->find($html)
        );
    }

    public function testFindDoesNotMatchAcrossClosingTagWithWhitespace(): void
    {
        $this->assertSame(
            ['<script>foo</script >', '<script>bar</script>'],
            (new ScriptFinder())->find('<div><script>foo</script ><p>text</p><script>bar</script></div>')
        );
    }

    public function testFindKeepsGreaterThanInsideQuotedAttribute(): void
    {
        $script = '<script data-x="a > b">foobar</script>';

        $this->assertSame([$script], (new ScriptFinder())->find('<div>' . $script . '</div>'));
    }

    public function testGetOpeningTag(): void
    {
        $scriptFinder = new ScriptFinder();

        $this->assertSame('<script data-x="a > b">', $scriptFinder->getOpeningTag('<script data-x="a > b">foo</script>'));
        $this->assertSame('<SCRIPT>', $scriptFinder->getOpeningTag('<SCRIPT>foo</SCRIPT>'));
        $this->assertSame('', $scriptFinder->getOpeningTag('<script-data>foo</script>'));
        $this->assertSame('', $scriptFinder->getOpeningTag('<div><script>foo</script></div>'));
    }

    public function testGetAttributes(): void
    {
        $attributes = (new ScriptFinder())->getAttributes(
            "<script TYPE='text/javascript' defer data-a=\"x > y\" data-b=unquoted data-a=\"second\">foo</script>"
        );

        $this->assertSame(
            ['type' => 'text/javascript', 'defer' => '', 'data-a' => 'x > y', 'data-b' => 'unquoted'],
            $attributes
        );
    }

    public function testGetAttributesOfNonScriptIsEmpty(): void
    {
        $this->assertSame([], (new ScriptFinder())->getAttributes('<script-data type="text/javascript">foo</script>'));
    }
}
