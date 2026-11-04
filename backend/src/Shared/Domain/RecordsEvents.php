<?php

declare(strict_types=1);

namespace Tutorias\Shared\Domain;

trait RecordsEvents
{
    /** @var list<DomainEvent> */
    private array $recordedEvents = [];

    protected function record(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /** @return list<DomainEvent> */
    public function pullEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }
}
