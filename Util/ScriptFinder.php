<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Util;

class ScriptFinder
{
    private const WHITESPACE = " \f\n\r\t\v\x00";
    private const SCRIPT_PATTERN = '#<script(?=[\s/>])((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>(.*?)</script\s*>#si';
    private const OPENING_TAG_PATTERN = '#^<script(?=[\s/>])((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>#i';
    private const ATTRIBUTE_PATTERN = '#([^\s"\'>/=]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+)))?#';
    private const JAVASCRIPT_TYPES = ['', 'text/javascript', 'application/javascript', 'module'];

    /**
     * @param string $html
     * @return string[]
     */
    public function find(string $html): array
    {
        if (trim($html, self::WHITESPACE) === '') {
            return [];
        }

        if (!preg_match_all(self::SCRIPT_PATTERN, $html, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $scripts = [];
        foreach ($matches as $match) {
            if (trim($match[2], self::WHITESPACE) === '') {
                continue;
            }

            if (false === $this->needsCsp($this->parseAttributes($match[1]))) {
                continue;
            }

            $scripts[] = $match[0];
        }

        return $scripts;
    }

    public function getOpeningTag(string $script): string
    {
        if (!preg_match(self::OPENING_TAG_PATTERN, $script, $match)) {
            return '';
        }

        return $match[0];
    }

    /**
     * @param string $script
     * @return array<string, string>
     */
    public function getAttributes(string $script): array
    {
        if (!preg_match(self::OPENING_TAG_PATTERN, $script, $match)) {
            return [];
        }

        return $this->parseAttributes($match[1]);
    }

    /**
     * @param string $attributes
     * @return array<string, string>
     */
    private function parseAttributes(string $attributes): array
    {
        preg_match_all(self::ATTRIBUTE_PATTERN, $attributes, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);

        $result = [];
        foreach ($matches as $match) {
            $name = strtolower((string)$match[1]);
            if (array_key_exists($name, $result)) {
                continue;
            }

            $result[$name] = (string)($match[2] ?? $match[3] ?? $match[4] ?? '');
        }

        return $result;
    }

    /**
     * @param array<string, string> $attributes
     * @return bool
     */
    private function needsCsp(array $attributes): bool
    {
        if (array_key_exists('nonce', $attributes) || array_key_exists('src', $attributes)) {
            return false;
        }

        $type = strtolower(trim($attributes['type'] ?? '', self::WHITESPACE));

        return in_array($type, self::JAVASCRIPT_TYPES, true);
    }
}
