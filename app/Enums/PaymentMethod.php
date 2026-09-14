<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Qris = 'qris';
    case Va = 'va';
    case Bca = 'bca';
    case Bni = 'bni';
    case Bri = 'bri';

    public static function fromProviderCode(string $providerCode): ?self
    {
        return match (strtoupper(trim($providerCode))) {
            'QRIS' => self::Qris,
            'VA', 'CIMBVA', 'BSIVA' => self::Va,
            'BCA', 'BCAVA', '014' => self::Bca,
            'BNI', 'BNIVA', '009' => self::Bni,
            'BRI', 'BRIVA', '002' => self::Bri,
            default => null,
        };
    }
}
