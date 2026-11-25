<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Integration\Util;

use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\CspUtilities\Util\ReplaceInlineScripts;
use Yireo\CspUtilities\ViewModel\CspMarker;

/**
 * @magentoAppArea frontend
 */
class ReplaceInlineScriptsTest extends TestCase
{
    public function testEmptyHtml()
    {
        $this->assertSame('', $this->getReplaceInlineScripts()->replace(''));
        $this->assertSame('', $this->getReplaceInlineScripts()->replaceMarked(''));
    }

    /**
     * @magentoConfigFixture current_store csp_utilities/settings/inline_script_mode nonce
     * @magentoCache full_page disabled
     */
    public function testAddsNoncesToMarkedScriptsInNonceMode()
    {
        $html = '<div><script ' . $this->marker() . '>var a = 1;</script>'
            . '<script ' . $this->marker() . ' type="text/javascript">var b = 2;</script>'
            . '<script type="text/x-magento-init">{}</script><script>var c = 3;</script></div>';
        $result = $this->getReplaceInlineScripts()->replaceMarked($html);

        $this->assertSame(2, preg_match_all('#<script nonce="([^"]+)"#', $result, $matches));
        $this->assertStringContainsString('<script type="text/x-magento-init">{}</script>', $result);
        $this->assertStringContainsString('<script>var c = 3;</script>', $result);
        $this->assertStringNotContainsString(CspMarker::ATTRIBUTE, $result);

        $nonces = $this->getScriptSrcNonces();
        foreach ($matches[1] as $nonce) {
            $this->assertContains(base64_decode($nonce), $nonces);
        }
    }

    /**
     * @magentoConfigFixture current_store csp_utilities/settings/inline_script_mode nonce
     * @magentoCache full_page disabled
     */
    public function testAddsNonceOnlyToOpeningTagInNonceMode()
    {
        $body = 'document.write(\'<script src="/a.js"><\\/script>\');var t = `<script>`;';
        $html = '<div><script ' . $this->marker() . '>' . $body . '</script></div>';
        $result = $this->getReplaceInlineScripts()->replaceMarked($html);

        $this->assertSame(1, substr_count($result, 'nonce='));
        $this->assertSame(1, preg_match('#^<div><script nonce="([^"]+)">(.*)</script></div>$#s', $result, $matches));
        $this->assertSame($body, $matches[2]);
        $this->assertContains(base64_decode($matches[1]), $this->getScriptSrcNonces());
    }

    /**
     * @magentoConfigFixture current_store csp_utilities/settings/inline_script_mode nonce
     * @magentoCache full_page disabled
     */
    public function testKeepsExistingNonceAndAddsNoHashesInNonceMode()
    {
        $script = "console.log('nonce-mode-without-hash');";
        $html = '<div><script nonce="existing">var a = 1;</script>'
            . '<script ' . $this->marker() . '>' . $script . '</script></div>';
        $result = $this->getReplaceInlineScripts()->replaceMarked($html);

        $this->assertStringContainsString('<script nonce="existing">var a = 1;</script>', $result);
        $this->assertSame(2, substr_count($result, 'nonce='));

        $hash = base64_encode(hash('sha256', $script, true));
        $this->assertNotContains($hash, $this->getScriptSrcHashes());
    }

    /**
     * @magentoConfigFixture current_store csp_utilities/settings/inline_script_mode nonce
     * @magentoCache full_page enabled
     */
    public function testFallsBackToHashesOnCacheablePagesInNonceMode()
    {
        $script = "console.log('nonce-mode-cached-page');";
        $result = $this->getReplaceInlineScripts()->replaceMarked('<script ' . $this->marker() . '>' . $script . '</script>');

        $this->assertSame('<script>' . $script . '</script>', $result);
        $this->assertContains(base64_encode(hash('sha256', $script, true)), $this->getScriptSrcHashes());
    }

    /**
     * @magentoConfigFixture current_store csp_utilities/settings/inline_script_mode hash
     */
    public function testSkipsNonJavaScriptInHashMode()
    {
        $magentoInit = '{"*":{"hash-mode-magento-init":{}}}';
        $template = '<p>hash-mode-template</p>';
        $html = '<div><script type="text/x-magento-init" ' . $this->marker() . '>' . $magentoInit . '</script>'
            . '<script type="text/x-magento-template" ' . $this->marker() . '>' . $template . '</script></div>';

        $result = $this->getReplaceInlineScripts()->replaceMarked($html);
        $this->assertSame($html, $result);

        $hashes = $this->getScriptSrcHashes();
        $this->assertNotContains(base64_encode(hash('sha256', $magentoInit, true)), $hashes);
        $this->assertNotContains(base64_encode(hash('sha256', $template, true)), $hashes);
    }

    /**
     * @magentoConfigFixture current_store csp_utilities/settings/inline_script_mode hash
     */
    public function testSkipsUnmarkedScriptsInHashMode()
    {
        $script = "console.log('hash-mode-unmarked');";
        $html = '<div><script>' . $script . '</script><script data-csp-utilities="forged">' . $script . '</script></div>';

        $this->assertSame($html, $this->getReplaceInlineScripts()->replaceMarked($html));
        $this->assertNotContains(base64_encode(hash('sha256', $script, true)), $this->getScriptSrcHashes());
    }

    /**
     * @magentoConfigFixture current_store csp_utilities/settings/inline_script_mode hash
     * @dataProvider getScripts
     */
    public function testAddsHashesInHashMode(string $openingTag, string $script)
    {
        $html = '<div>' . sprintf($openingTag, $this->marker()) . $script . '</script></div>';
        $expected = '<div>' . sprintf($openingTag, '') . $script . '</script></div>';
        $expected = str_replace([' >', '  '], ['>', ' '], $expected);
        $this->assertSame($expected, $this->getReplaceInlineScripts()->replaceMarked($html));

        $hash = base64_encode(hash('sha256', $script, true));
        $this->assertContains($hash, $this->getScriptSrcHashes(), 'No hash for ' . $openingTag);
    }

    public static function getScripts(): array
    {
        return [
            ['<script %s>', "console.log('plain');"],
            ['<script type="text/javascript" %s>', "console.log('text/javascript');"],
            ['<script type="application/javascript" %s defer>', "console.log('application/javascript');"],
            ['<script type="module" %s>', "console.log('module');"],
        ];
    }

    /**
     * @magentoConfigFixture current_store csp_utilities/settings/inline_script_mode hash
     */
    public function testDeprecatedReplaceAddsHashesOfUnmarkedScripts()
    {
        $script = "console.log('deprecated-replace');";
        $html = '<div><script>' . $script . '</script></div>';

        $this->assertSame($html, $this->getReplaceInlineScripts()->replace($html));
        $this->assertContains(base64_encode(hash('sha256', $script, true)), $this->getScriptSrcHashes());
    }

    private function marker(): string
    {
        return ObjectManager::getInstance()->get(CspMarker::class)->getAttribute();
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
