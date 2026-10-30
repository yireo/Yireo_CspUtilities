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
            ['<div><script type="module">foobar</script></div>', 0],
            ['<div><script type="text/x-magento-template">foobar</script></div>', 0],
        ];
    }
}
