<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Outbox;

interface OutboxHandler
{
    /** @return list<string> tipos de evento que atiende */
    public function subscribedTo(): array;

    public function handle(OutboxMessage $message): void;
}
