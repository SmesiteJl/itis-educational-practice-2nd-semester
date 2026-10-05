<?php

namespace App\Tests\Unit\Bot;

use App\Bot\PromptBuilder;
use PHPUnit\Framework\TestCase;

class PromptBuilderTest extends TestCase
{
    private PromptBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new PromptBuilder();
    }

    public function testReplacesPlaceholder(): void
    {
        $prompt = $this->builder->build('переведи на русский язык {text}', 'may the force be with you');

        $this->assertSame('переведи на русский язык may the force be with you', $prompt);
    }

    public function testAppendsTextWhenNoPlaceholder(): void
    {
        $prompt = $this->builder->build('Переведи на английский', 'кот');

        $this->assertSame("Переведи на английский\n\nкот", $prompt);
    }

}
