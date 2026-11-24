<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Yireo\CspUtilities\Config\Source\InlineScriptOptions;

class Config
{
    private const ALLOWED_MODES = [InlineScriptOptions::NONCE, InlineScriptOptions::HASH];

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function getInlineScriptMode(): string
    {
        $value = (string)$this->scopeConfig->getValue(
            'csp_utilities/settings/inline_script_mode',
            ScopeInterface::SCOPE_STORE
        );

        if (in_array($value, self::ALLOWED_MODES, true)) {
            return $value;
        }

        return InlineScriptOptions::NONCE;
    }

    public function generateNonces(): bool
    {
        return $this->getInlineScriptMode() === InlineScriptOptions::NONCE;
    }

    public function generateHashes(): bool
    {
        return $this->getInlineScriptMode() === InlineScriptOptions::HASH;
    }
}
