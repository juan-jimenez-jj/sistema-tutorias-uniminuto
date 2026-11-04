<?php

declare(strict_types=1);

namespace Tutorias\Notifications\Application;

final readonly class Contact
{
    public function __construct(public int $id, public string $name, public string $email)
    {
    }
}
