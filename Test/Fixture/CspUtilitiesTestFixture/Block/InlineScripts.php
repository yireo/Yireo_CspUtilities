<?php declare(strict_types=1);

namespace Yireo\CspUtilitiesTestFixture\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Yireo\CspUtilities\Util\ReplaceInlineScripts;

class InlineScripts extends Template
{
    private ReplaceInlineScripts $replaceInlineScripts;

    public function __construct(
        Context $context,
        ReplaceInlineScripts $replaceInlineScripts,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->replaceInlineScripts = $replaceInlineScripts;
    }

    protected function _afterToHtml($html)
    {
        return $this->replaceInlineScripts->replace((string)$html);
    }
}
