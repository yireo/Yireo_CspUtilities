<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;
use Yireo\CspUtilities\Util\ReplaceInlineScripts;

class AddCspInlineScripts
{
    private readonly ReplaceInlineScripts $replaceInlineScripts;
    private readonly ScopeConfigInterface $scopeConfig;
    private readonly HttpRequest $request;
    private readonly array $allowedBlocks = [];

    public function __construct(
        ReplaceInlineScripts $replaceInlineScripts,
        ScopeConfigInterface $scopeConfig,
        HttpRequest $request,
        array $allowedBlocks = [],
    ) {
        $this->replaceInlineScripts = $replaceInlineScripts;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->allowedBlocks = $allowedBlocks;
    }

    public function afterToHtml(Template $block, $html): string
    {
        if (false === in_array((string)$block->getNameInLayout(), $this->allowedBlocks(), true)) {
            return (string)$html;
        }

        return $this->replaceInlineScripts->replace((string)$html);
    }
}
