<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Util;

class ReplaceInlineScripts
{
    private ScriptFinder $scriptFinder;
    private CspNonceGenerator $cspNonceGenerator;

    public function __construct(
        ScriptFinder $scriptFinder,
        CspNonceGenerator $cspNonceGenerator,
    ) {
        $this->scriptFinder = $scriptFinder;
        $this->cspNonceGenerator = $cspNonceGenerator;
    }

    public function replace(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        $scripts = $this->scriptFinder->find($html);
        foreach ($scripts as $script) {
            $nonce = $this->cspNonceGenerator->getNonce();
            if (empty($nonce)) {
                break;
            }

            $newScript = str_replace('<script', '<script nonce="'.$nonce.'"', $script);
            $html = str_replace($script, $newScript, $html);
        }

        return $html;
    }
}
