<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Util;

use Yireo\CspUtilities\Config\Config;

class ReplaceInlineScripts
{
    private ScriptFinder $scriptFinder;
    private CspNonceGenerator $cspNonceGenerator;
    private Config $config;
    private CspHashGenerator $cspHashGenerator;

    public function __construct(
        ScriptFinder $scriptFinder,
        CspNonceGenerator $cspNonceGenerator,
        CspHashGenerator $cspHashGenerator,
        Config $config
    ) {
        $this->scriptFinder = $scriptFinder;
        $this->cspNonceGenerator = $cspNonceGenerator;
        $this->cspHashGenerator = $cspHashGenerator;
        $this->config = $config;
    }

    public function replace(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        return $this->replaceScripts($html, $this->scriptFinder->find($html));
    }

    /**
     * @param string $html
     * @param string[] $scripts
     * @return string
     */
    public function replaceScripts(string $html, array $scripts): string
    {
        foreach ($scripts as $script) {
            if (empty($script) || false === str_contains($html, $script)) {
                continue;
            }

            if ($this->config->generateNonces()) {
                $html = $this->addCspNonce($html, $script);
            }

            if ($this->config->generateHashes()) {
                $this->addCspHash($script);
            }
        }

        return $html;
    }

    private function addCspNonce(string $html, string $script): string
    {
        $nonce = $this->cspNonceGenerator->getNonce();
        if (empty($nonce)) {
            return $html;
        }

        $newScript = (string)preg_replace('#^<script#i', '<script nonce="'.$nonce.'"', $script, 1);
        return str_replace($script, $newScript, $html);
    }

    private function addCspHash(string $script): void
    {
        $script = (string)preg_replace('#^<script\b[^>]*>#i', '', $script);
        $script = (string)preg_replace('#</script>$#i', '', $script);
        $this->cspHashGenerator->generate($script);
    }
}
