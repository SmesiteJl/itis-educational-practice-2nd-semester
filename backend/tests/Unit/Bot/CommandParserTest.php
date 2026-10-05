<?php

namespace App\Tests\Unit\Bot;

use App\Bot\CommandParser;
use PHPUnit\Framework\TestCase;

class CommandParserTest extends TestCase
{
    private CommandParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CommandParser();
    }

    public function testParsesCommandFromTask(): void
    {
        $parsed = $this->parser->parse('!перевод may the force be with you');

        $this->assertNotNull($parsed);
        $this->assertSame('перевод', $parsed->getName());
        $this->assertSame('may the force be with you', $parsed->getText());
    }

    public function testNotACommand(): void
    {
        $this->assertNull($this->parser->parse('привет всем'));
    }
}
