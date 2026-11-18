<?php
declare(strict_types=1);

namespace VMS\Core\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class RequiresAuth
{
}
