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

        $scripts = $this->scriptFinder->find($html);
        foreach ($scripts as $script) {
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

        $newScript = str_replace('<script', '<script nonce="'.$nonce.'"', $script);
        return str_replace($script, $newScript, $html);
    }

    private function addCspHash(string $script): void
    {
        $script = preg_replace('/<script([^>]?)>/', '', $script);
        $script = str_replace('</script>', '', $script);
        $this->cspHashGenerator->generate($script);
    }
}
