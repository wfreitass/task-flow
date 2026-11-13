<?php

namespace App\Shared\Domain;

enum SortDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';
}
