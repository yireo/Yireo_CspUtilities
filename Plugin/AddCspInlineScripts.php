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
        if ($html === '') {
            return $html;
        }

        if ($this->isFlagged($block)) {
            return $this->replaceInlineScripts->replaceMarked($html);
        }

        if ($this->isInAllowlist($block)) {
            return $this->replaceInlineScripts->replace($html);
        }

        return $html;
    }

    private function isAllowed(Template $block): bool
    {
        return $this->isFlagged($block) || $this->isInAllowlist($block);
    }

    private function isFlagged(Template $block): bool
    {
        return true === (bool)$block->getData(self::BLOCK_FLAG);
    }

    private function isInAllowlist(Template $block): bool
    {
        if ($this->allowedBlocks === []) {
            return false;
        }

        return in_array((string)$block->getNameInLayout(), $this->allowedBlocks, true);
    }
}
