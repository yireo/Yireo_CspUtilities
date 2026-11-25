<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Util;

use Yireo\CspUtilities\ViewModel\CspMarker;

class ReplaceInlineScripts
{
    private const NONCE_PATTERN = '#^[A-Za-z0-9+/]+={0,2}$#';
    private const CLOSING_TAG_PATTERN = '#</script\s*>$#i';
    private const ATTRIBUTE_PATTERN = '#\s*([^\s"\'>/=]+)(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+))?#';

    public function __construct(
        private readonly ScriptFinder $scriptFinder,
        private readonly CspNonceGenerator $cspNonceGenerator,
        private readonly CspHashGenerator $cspHashGenerator,
        private readonly InlineScriptMode $inlineScriptMode,
        private readonly CspMarker $cspMarker
    ) {
    }

    /**
     * Replace all inline scripts in the HTML, including scripts that were injected into it
     *
     * @deprecated Use replaceMarked() instead, so that only scripts with the marker of the current request are trusted
     * @param string $html
     * @return string
     */
    public function replace(string $html): string
    {
        if ($html === '') {
            return '';
        }

        return $this->replaceScripts($html, $this->scriptFinder->find($html));
    }

    /**
     * Only replace the scripts that carry the marker of the current request, see CspMarker::getAttribute()
     *
     * @param string $html
     * @return string
     */
    public function replaceMarked(string $html): string
    {
        if ($html === '' || false === stripos($html, CspMarker::ATTRIBUTE)) {
            return $html;
        }

        $scripts = array_filter(
            $this->scriptFinder->find($html),
            fn (string $script): bool => $this->isMarked($script)
        );

        return $this->replaceScripts($html, array_values($scripts));
    }

    /**
     * Replace the given scripts, which the caller is responsible for to only include trusted scripts
     *
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

            $openingTag = $this->scriptFinder->getOpeningTag($script);
            if ($openingTag === '') {
                continue;
            }

            $newOpeningTag = $this->removeMarker($openingTag);
            if ($this->inlineScriptMode->useNonces()) {
                $newOpeningTag = $this->addCspNonce($newOpeningTag);
            }

            if ($this->inlineScriptMode->useHashes()) {
                $this->addCspHash($script, $openingTag);
            }

            if ($newOpeningTag !== $openingTag) {
                $html = str_replace($script, $newOpeningTag . substr($script, strlen($openingTag)), $html);
            }
        }

        return $html;
    }

    private function isMarked(string $script): bool
    {
        $marker = $this->scriptFinder->getAttributes($script)[CspMarker::ATTRIBUTE] ?? '';

        return $marker !== '' && hash_equals($this->cspMarker->getToken(), $marker);
    }

    private function removeMarker(string $openingTag): string
    {
        if (false === stripos($openingTag, CspMarker::ATTRIBUTE)) {
            return $openingTag;
        }

        $attributes = (string)preg_replace_callback(
            self::ATTRIBUTE_PATTERN,
            fn (array $match): string => strtolower($match[1]) === CspMarker::ATTRIBUTE ? '' : $match[0],
            substr($openingTag, 7, -1)
        );

        return '<script' . $attributes . '>';
    }

    private function addCspNonce(string $openingTag): string
    {
        $nonce = $this->cspNonceGenerator->getNonce();
        if (false === (bool)preg_match(self::NONCE_PATTERN, $nonce)) {
            return $openingTag;
        }

        return '<script nonce="' . $nonce . '"' . substr($openingTag, 7);
    }

    private function addCspHash(string $script, string $openingTag): void
    {
        $body = (string)preg_replace(self::CLOSING_TAG_PATTERN, '', substr($script, strlen($openingTag)));
        $this->cspHashGenerator->generate($body);
    }
}
