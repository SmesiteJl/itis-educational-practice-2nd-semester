<?php

namespace App\Chat;

class NicknameValidator
{
    public const MIN_LENGTH = 2;
    public const MAX_LENGTH = 24;

    public function validate(string $nickname): ?string
    {

        $length = mb_strlen($nickname);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            return 'Ник должен быть от ' . self::MIN_LENGTH . ' до ' . self::MAX_LENGTH . ' символов';
        }

        if (!preg_match('/^[\p{L}\p{N} _-]+$/u', $nickname)) {
            return 'В нике можно использовать только буквы, цифры, пробел, _ и -';
        }

        return null;
    }

    public function isSame(string $first, string $second): bool
    {
        return mb_strtolower($first) === mb_strtolower($second);
    }
}
