<?php

namespace App\Contracts;

use App\Models\ItensList;

interface ListDataAssembler
{
    public function assemble(ItensList $list): array;
}
