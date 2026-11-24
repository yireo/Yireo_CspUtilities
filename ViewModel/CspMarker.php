<?php declare(strict_types=1);

namespace Yireo\CspUtilities\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;

class CspMarker implements ArgumentInterface
{
    public const ATTRIBUTE = 'data-csp-utilities';

    private string $token = '';

    public function getToken(): string
    {
        if ($this->token === '') {
            $this->token = bin2hex(random_bytes(16));
        }

        return $this->token;
    }

    public function getAttribute(): string
    {
        return self::ATTRIBUTE . '="' . $this->getToken() . '"';
    }
}
