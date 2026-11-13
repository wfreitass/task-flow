<?php

namespace App\Shared\Application\Port;

interface ClockInterface
{
    /**
     * Instante atual em UTC.
     */
    public function now(): \DateTimeImmutable;
}
