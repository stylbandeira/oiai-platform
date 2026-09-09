<?php

namespace App\Actions\List;

use App\Models\ItensList;
use App\Repositories\ListRepository;

class DestroyListAction
{
    public function __construct(
        private ListRepository $listRepository,
    ) {}
    public function execute(ItensList $list)
    {
        $this->listRepository->delete($list->id);

        return response([
            'message' => 'Lista deletada com sucesso!',
        ]);
    }
}
