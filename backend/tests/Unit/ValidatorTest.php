<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Validators\Validator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testValidInputReturnsNoErrors(): void
    {
        $errors = Validator::validate(
            ['name' => 'Alex Rivera', 'email' => 'alex@example.com', 'age' => 21],
            [
                'name' => ['required', 'string', 'min:2', 'max:50'],
                'email' => ['required', 'email'],
                'age' => ['integer', 'min:18'],
            ]
        );

        self::assertSame([], $errors);
    }

    public function testRequiredAndEmailRulesReturnFieldErrors(): void
    {
        $errors = Validator::validate(
            ['name' => '', 'email' => 'not-an-email'],
            [
                'name' => ['required'],
                'email' => ['required', 'email'],
            ]
        );

        self::assertArrayHasKey('name', $errors);
        self::assertArrayHasKey('email', $errors);
        self::assertContains('This field is required.', $errors['name']);
        self::assertContains('Enter a valid email address.', $errors['email']);
    }

    public function testAllowedValuesRuleRejectsUnknownValue(): void
    {
        $errors = Validator::validate(
            ['status' => 'archived'],
            ['status' => ['in:active,inactive']]
        );

        self::assertArrayHasKey('status', $errors);
    }

    public function testUnsupportedRuleThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Validator::validate(['name' => 'Alex'], ['name' => ['unknown']]);
    }
}
