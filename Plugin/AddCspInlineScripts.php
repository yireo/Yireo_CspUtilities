<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Plugin;

use Magento\Framework\View\Element\Template;
use Yireo\CspUtilities\Util\ReplaceInlineScripts;
use Yireo\CspUtilities\ViewModel\CspMarker;

class AddCspInlineScripts
{
    private const BLOCK_FLAG = 'csp_utilities';
    private const BLOCK_MARKER = 'csp_marker';

    /**
     * @param ReplaceInlineScripts $replaceInlineScripts
     * @param CspMarker $cspMarker
     * @param string[] $allowedBlocks
     */
    public function __construct(
        private readonly ReplaceInlineScripts $replaceInlineScripts,
        private readonly CspMarker $cspMarker,
        private readonly array $allowedBlocks = []
    ) {
    }

    public function beforeToHtml(Template $block): void
    {
        if ($this->isAllowed($block) && false === $block->hasData(self::BLOCK_MARKER)) {
            $block->setData(self::BLOCK_MARKER, $this->cspMarker);
        }
    }

    public function afterToHtml(Template $block, mixed $html): string
    {
        $html = (string)$html;
        if ($html === '' || false === $this->isAllowed($block)) {
            return $html;
        }

        return $this->replaceInlineScripts->replace($html);
    }

    private function isAllowed(Template $block): bool
    {
        if (true === (bool)$block->getData(self::BLOCK_FLAG)) {
            return true;
        }

        if ($this->allowedBlocks === []) {
            return false;
        }

        return in_array((string)$block->getNameInLayout(), $this->allowedBlocks, true);
    }
}
