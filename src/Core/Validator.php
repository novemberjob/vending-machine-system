<?php
declare(strict_types=1);

namespace VMS\Core;

/**
 * Small rule-based validator. Rules are strings such as "required", "max:100",
 * "in:asc,desc", "confirmed:password_confirmation".
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $values = [];

    /** @param array<string, mixed> $data */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];
        $this->values = $data;

        foreach ($rules as $field => $fieldRules) {
            foreach ((array) $fieldRules as $rule) {
                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
                $value = $data[$field] ?? null;

                if ($name === 'required') {
                    if ($value === null || (is_string($value) && trim($value) === '')) {
                        $this->addError($field, 'This field is required.');
                        break;
                    }
                    continue;
                }

                if ($value === null || (is_string($value) && trim($value) === '')) {
                    continue; // "required" is responsible for empty values
                }

                switch ($name) {
                    case 'numeric':
                        if (!is_numeric($value)) {
                            $this->addError($field, 'Must be a number.');
                        }
                        break;

                    case 'positive':
                        if (!is_numeric($value) || (float) $value <= 0) {
                            $this->addError($field, 'Must be greater than zero.');
                        }
                        break;

                    case 'integer':
                        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                            $this->addError($field, 'Must be a whole number.');
                        }
                        break;

                    case 'min':
                        if (is_numeric($value) && (float) $value < (float) $parameter) {
                            $this->addError($field, "Must be at least {$parameter}.");
                        } elseif (is_string($value) && mb_strlen($value) < (int) $parameter) {
                            $this->addError($field, "Must be at least {$parameter} characters.");
                        }
                        break;

                    case 'max':
                        // Numeric strings are compared numerically; everything else is measured by length.
                        if (is_numeric($value) && preg_match('/^-?\d+(\.\d+)?$/', (string) $value)) {
                            if ((float) $value > (float) $parameter) {
                                $this->addError($field, "Must not exceed {$parameter}.");
                            }
                        } elseif (is_string($value) && mb_strlen($value) > (int) $parameter) {
                            $this->addError($field, "Must not exceed {$parameter} characters.");
                        }
                        break;

                    case 'email':
                        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                            $this->addError($field, 'Must be a valid email address.');
                        }
                        break;

                    case 'in':
                        if (!in_array((string) $value, explode(',', (string) $parameter), true)) {
                            $this->addError($field, 'Invalid value.');
                        }
                        break;

                    case 'confirmed':
                        if (($data[$parameter] ?? null) !== $value) {
                            $this->addError($field, 'Confirmation does not match.');
                        }
                        break;
                }
            }
        }

        return $this->errors === [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function error(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /** @return array<string, mixed> */
    public function values(): array
    {
        return $this->values;
    }

    /** @return array<string, list<string>> */
    public static function productRules(): array
    {
        return [
            'name'               => ['required', 'max:100'],
            'price'              => ['required', 'numeric', 'positive'],
            'quantity_available' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, list<string>> */
    public static function purchaseRules(int $available): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:' . $available],
        ];
    }

    /** @return array<string, list<string>> */
    public static function registerRules(): array
    {
        return [
            'name'                  => ['required', 'max:100'],
            'email'                 => ['required', 'email', 'max:255'],
            'password'              => ['required', 'min:8'],
            'password_confirmation' => ['required', 'confirmed:password'],
        ];
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }
}
