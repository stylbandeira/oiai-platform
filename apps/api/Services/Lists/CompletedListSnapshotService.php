<?php

namespace App\Services\Lists;

use App\Contracts\ListDataAssembler;
use App\Models\CompletedList;
use App\Models\ItensList;

class CompletedListSnapshotService
{
    public const VERSION = '1.0';

    public function __construct(private ListDataAssembler $assembler) {}

    public function store(ItensList $list): CompletedList
    {
        $data = $this->assembler->assemble($list);

        return CompletedList::query()->updateOrCreate(
            ['list_id' => $list->id],
            [
                'list_data' => $data,
                'version' => self::VERSION,
                'total_price' => $data['total'],
                'completed_at' => now(),
            ],
        );
    }

    public function get(ItensList $list): CompletedList
    {
        return CompletedList::query()->where('list_id', $list->id)->firstOrFail();
    }
}
