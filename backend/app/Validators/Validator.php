<?php

declare(strict_types=1);

namespace App\Validators;

final class Validator
{
    /**
     * Validate input using rules such as required, string, email, integer,
     * min:2, max:120, and in:active,inactive.
     *
     * @param array<string, mixed> $data
     * @param array<string, array<int, string>> $rules
     * @return array<string, string[]>
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $isEmpty = $value === null || $value === '';

            foreach ($fieldRules as $rule) {
                [$name, $argument] = array_pad(explode(':', $rule, 2), 2, null);

                if ($name === 'required') {
                    if ($isEmpty) {
                        $errors[$field][] = 'This field is required.';
                    }
                    continue;
                }

                if ($isEmpty) {
                    continue;
                }

                $valid = match ($name) {
                    'string' => is_string($value),
                    'email' => is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
                    'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
                    'min' => self::passesMinimum($value, (int) $argument),
                    'max' => self::passesMaximum($value, (int) $argument),
                    'in' => in_array((string) $value, explode(',', (string) $argument), true),
                    default => throw new \InvalidArgumentException('Unsupported validation rule: ' . $name),
                };

                if (!$valid) {
                    $errors[$field][] = self::message($name, $argument);
                }
            }
        }

        return $errors;
    }

    private static function passesMinimum(mixed $value, int $limit): bool
    {
        if (is_string($value)) {
            return mb_strlen($value) >= $limit;
        }

        if (is_int($value) || is_float($value)) {
            return $value >= $limit;
        }

        return false;
    }

    private static function passesMaximum(mixed $value, int $limit): bool
    {
        if (is_string($value)) {
            return mb_strlen($value) <= $limit;
        }

        if (is_int($value) || is_float($value)) {
            return $value <= $limit;
        }

        return false;
    }

    private static function message(string $rule, ?string $argument): string
    {
        return match ($rule) {
            'string' => 'This field must be a string.',
            'email' => 'Enter a valid email address.',
            'integer' => 'This field must be an integer.',
            'min' => 'This field must be at least ' . $argument . ' characters or units.',
            'max' => 'This field may not exceed ' . $argument . ' characters or units.',
            'in' => 'The selected value is not allowed.',
            default => 'This field is invalid.',
        };
    }
}
