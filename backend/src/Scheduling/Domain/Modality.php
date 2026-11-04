<?php

declare(strict_types=1);

namespace Tutorias\Scheduling\Domain;

enum Modality: string
{
    case Presencial = 'presencial';
    case Virtual = 'virtual';
}
