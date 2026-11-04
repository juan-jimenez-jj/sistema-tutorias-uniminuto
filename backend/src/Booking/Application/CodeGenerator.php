<?php

declare(strict_types=1);

namespace Tutorias\Booking\Application;

/** Genera códigos legibles tipo TUT-8K2F9Q (alfabeto sin caracteres ambiguos como 0/O, 1/I). */
final class CodeGenerator
{
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public function next(): string
    {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return 'TUT-' . $code;
    }
}
