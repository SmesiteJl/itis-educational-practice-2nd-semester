<?php

namespace App\Bot;

class PromptBuilder
{
    public const PLACEHOLDER = '{text}';

    public function build(string $template, string $text): string
    {
        if (str_contains($template, self::PLACEHOLDER)) {
            return str_replace(self::PLACEHOLDER, $text, $template);
        }

        if ($text === '') {
            return $template;
        }

        return $template . "\n\n" . $text;
    }

    public function needsText(string $template): bool
    {
        return str_contains($template, self::PLACEHOLDER);
    }
}
