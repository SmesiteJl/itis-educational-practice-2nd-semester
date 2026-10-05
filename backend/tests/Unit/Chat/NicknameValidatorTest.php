<?php

namespace App\Tests\Unit\Chat;

use App\Chat\NicknameValidator;
use PHPUnit\Framework\TestCase;

class NicknameValidatorTest extends TestCase
{
    private NicknameValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new NicknameValidator();
    }

    public function testNormalNicknames(): void
    {
        $this->assertNull($this->validator->validate('Вася'));
        $this->assertTrue($this->validator->isSame('Вася', 'вАСЯ'));
    }

    public function testInvalidNickname(): void
    {
        $this->assertNotNull($this->validator->validate('<script>'));
        $this->assertNotNull($this->validator->validate('Я'));
    }
}
