<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Yireo\CspUtilities\Config\Source\InlineScriptOptions;

class Config
{
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function getInlineScriptMode(): string
    {
        $value = $this->scopeConfig->getValue('csp_utilities/settings/inline_script_mode');
        if (false === empty($value)) {
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
