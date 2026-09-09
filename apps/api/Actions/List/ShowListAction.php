<?php

namespace App\Actions\List;

use App\Contracts\ListDataAssembler;
use App\Models\ItensList;
use App\Services\Lists\CompletedListSnapshotService;

class ShowListAction
{
    public function __construct(
        private ListDataAssembler $assembler,
        private CompletedListSnapshotService $completedLists,
    ) {}

    public function execute(ItensList $list)
    {
        $responseList = $list->status === ItensList::STATUS_COMPLETED
            ? $this->completedLists->get($list)->list_data
            : $this->assembler->assemble($list);

        return response([
            'list' => $responseList,
            'optimized' => (bool) $responseList['optimized'],
        ]);
    }
}
