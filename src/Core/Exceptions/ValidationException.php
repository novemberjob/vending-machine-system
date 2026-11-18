<?php
declare(strict_types=1);

namespace VMS\Core\Exceptions;

final class ValidationException extends \InvalidArgumentException
{
    /** @param array<string, string> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Validation failed.');
    }
}
