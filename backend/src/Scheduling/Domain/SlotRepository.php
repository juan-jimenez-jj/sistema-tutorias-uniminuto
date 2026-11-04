<?php

declare(strict_types=1);

namespace Tutorias\Scheduling\Domain;

interface SlotRepository
{
    public function findById(int $id): ?AvailabilitySlot;

    /** @return list<AvailabilitySlot> */
    public function activeSlotsOfTeacherOn(int $teacherId, Weekday $weekday): array;

    public function save(AvailabilitySlot $slot): void;
}
