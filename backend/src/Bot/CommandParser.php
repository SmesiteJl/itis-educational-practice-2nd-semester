<?php

namespace App\Bot;

class CommandParser
{
    public function isCommand(string $message): bool
    {
        return str_starts_with($message, '!');
    }

    public function parse(string $message): ?ParsedCommand
    {
        if (!$this->isCommand($message)) {
            return null;
        }

        $parts = preg_split('/\s+/u', mb_substr($message, 1), 2);

        $name = mb_strtolower($parts[0]);
        if ($name === '') {
            return null;
        }

        $text = '';
        if (isset($parts[1])) {
            $text = trim($parts[1]);
        }

        return new ParsedCommand($name, $text);
    }
}
