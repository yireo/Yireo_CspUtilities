<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class InlineScriptOptions implements OptionSourceInterface
{
    const NONCE = 'nonce';
    const HASH = 'hash';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        $options = [
            ['value' => self::NONCE, 'label' => __('Generate CSP nonces for Yireo extensions')],
            ['value' => self::HASH, 'label' => __('Generate CSP hashes for Yireo extensions')],
        ];

        return $options;
    }
}
