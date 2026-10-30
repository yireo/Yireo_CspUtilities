<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Integration\Util;

use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\CspUtilities\Util\ReplaceInlineScripts;

/**
 * @magentoAppArea frontend
 */
class ReplaceInlineScriptsTest extends TestCase
{
    public function testEmptyHtml()
    {
        $this->assertSame('', $this->getReplaceInlineScripts()->replace(''));
    }

    /**
     * @magentoConfigFixture current_store csp_utilities/settings/inline_script_mode nonce
     */
    public function testAddsNoncesInNonceMode()
    {
        $html = '<div><script>var a = 1;</script><script type="text/javascript">var b = 2;</script>'
            . '<script type="text/x-magento-init">{}</script></div>';
        $result = $this->getReplaceInlineScripts()->replace($html);

        $this->assertSame(2, preg_match_all('#<script nonce="([^"]+)"#', $result, $matches));
        $this->assertStringContainsString('<script type="text/x-magento-init">{}</script>', $result);

        $nonces = $this->getScriptSrcNonces();
        foreach ($matches[1] as $nonce) {
            $this->assertContains(base64_decode($nonce), $nonces);
        }
    }

    /**
     * @magentoConfigFixture current_store csp_utilities/settings/inline_script_mode hash
     * @dataProvider getScripts
     */
    public function testAddsHashesInHashMode(string $openingTag, string $script)
    {
        $html = '<div>' . $openingTag . $script . '</script></div>';
        $this->assertSame($html, $this->getReplaceInlineScripts()->replace($html));

        $hash = base64_encode(hash('sha256', $script, true));
        $this->assertContains($hash, $this->getScriptSrcHashes(), 'No hash for ' . $openingTag);
    }

    public static function getScripts(): array
    {
        return [
            ['<script>', "console.log('plain');"],
            ['<script type="text/javascript">', "console.log('text/javascript');"],
            ['<script type="application/javascript" defer>', "console.log('application/javascript');"],
        ];
    }

    private function getReplaceInlineScripts(): ReplaceInlineScripts
    {
        return ObjectManager::getInstance()->get(ReplaceInlineScripts::class);
    }

    /**
     * @return string[]
     */
    private function getScriptSrcHashes(): array
    {
        $hashes = [];
        foreach ($this->getScriptSrcPolicies() as $policy) {
            $hashes = array_merge($hashes, array_keys($policy->getHashes()));
        }

        return $hashes;
    }

    /**
     * @return string[]
     */
    private function getScriptSrcNonces(): array
    {
        $nonces = [];
        foreach ($this->getScriptSrcPolicies() as $policy) {
            $nonces = array_merge($nonces, $policy->getNonceValues());
        }

        return $nonces;
    }

    /**
     * @return FetchPolicy[]
     */
    private function getScriptSrcPolicies(): array
    {
        $policies = [];
        foreach (ObjectManager::getInstance()->get(DynamicCollector::class)->collect() as $policy) {
            if ($policy instanceof FetchPolicy && $policy->getId() === 'script-src') {
                $policies[] = $policy;
            }
        }

        return $policies;
    }
}
