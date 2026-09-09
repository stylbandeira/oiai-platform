<?php

namespace App\Actions\List;

use App\Http\Requests\List\ListUpdateRequest;
use App\Models\ItensList;
use App\Repositories\ListProductsRepository;
use App\Repositories\ListRepository;
use App\Services\Lists\CompletedListSnapshotService;
use Illuminate\Support\Facades\DB;

class UpdateListAction
{
    public function __construct(
        private ListRepository $listRepository,
        private ListProductsRepository $listProductsRepository,
        private CompletedListSnapshotService $completedLists,
    ) {}

    public function execute(ListUpdateRequest $request, ItensList $list)
    {
        DB::transaction(function () use ($request, $list) {
            $list->load('listProducts');
            $wasCompleted = $list->status === ItensList::STATUS_COMPLETED;
            $this->listRepository->update($list->id, $request->safe()->except('items'));

            if ($request->has('items')) {
                $completedProductIds = $this->listRepository->getCompletedProductIds($list);
                $this->listRepository->deleteIncompleteItems($list);

                foreach ($request->validated()['items'] as $item) {
                    if (in_array($item['product_id'], $completedProductIds)) {
                        continue;
                    }

                    $this->listProductsRepository->createProductOnList(
                        $item['product_id'],
                        $list->id,
                        ['quantity' => $item['quantity']]
                    );
                }
            }

            $list->refresh();

            if (! $wasCompleted && $list->status === ItensList::STATUS_COMPLETED) {
                $this->completedLists->store($list);
            }
        });

        return response(['list' => $list->fresh()]);
    }
}
