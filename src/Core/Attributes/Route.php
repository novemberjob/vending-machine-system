<?php
declare(strict_types=1);

namespace VMS\Core\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Route
{
    /** @param string|list<string> $methods */
    public function __construct(
        public readonly string $path,
        public readonly string|array $methods = 'GET',
        public readonly ?string $name = null,
        public readonly bool $api = false,
    ) {
    }

    /** @return list<string> */
    public function httpMethods(): array
    {
        $methods = is_array($this->methods) ? $this->methods : [$this->methods];
        return array_map('strtoupper', $methods);
    }
}
