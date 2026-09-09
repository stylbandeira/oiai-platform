<?php

namespace App\Actions\ListItens;

use App\Http\Requests\ListItens\ListItensUpdateRequest;
use App\Models\ItensList;
use App\Repositories\ListProductsRepository;

class UpdateListItensAction
{
    public function __construct(
        private ListProductsRepository $listProductsRepository
    ) {}

    public function execute(ListItensUpdateRequest $request, ItensList $list)
    {
        $list->load('listProducts');

        try {
            $list->listProducts()->update([
                'completed' => false,
            ]);

            $completedItems = $request->validated()['completed_items'];

            if (!empty($completedItems)) {
                $this->listProductsRepository->updateProductsOnList($completedItems, $list->id, ['completed' => true]);
            }

            return response([
                'message' => 'Itens marcados como concluídos com sucesso',
                'completed_count' => count($completedItems),
            ]);
        } catch (\Throwable $th) {
            return response([
                'message' => 'Erro ao atualizar lista',
                'error' => $th->getMessage(),
            ]);
        }
    }
}
