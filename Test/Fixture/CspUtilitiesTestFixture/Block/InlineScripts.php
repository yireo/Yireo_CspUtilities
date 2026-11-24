<?php declare(strict_types=1);

namespace Yireo\CspUtilitiesTestFixture\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Yireo\CspUtilities\Util\ReplaceInlineScripts;
use Yireo\CspUtilities\ViewModel\CspMarker;

class InlineScripts extends Template
{
    public function __construct(
        Context $context,
        private readonly ReplaceInlineScripts $replaceInlineScripts,
        private readonly CspMarker $cspMarker,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getCspMarker(): CspMarker
    {
        return $this->cspMarker;
    }

    protected function _afterToHtml($html)
    {
        return $this->replaceInlineScripts->replace((string)$html);
    }
}
