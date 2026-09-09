<?php

namespace App\Actions\List;

use App\Http\Resources\ClientListResource;
use App\Models\ItensList;
use App\Repositories\ListRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IndexListAction
{
    public function __construct(
        private ListRepository $listRepository
    ) {}
    public function execute(Request $request)
    {
        $user = Auth::user();

        $itensList = $this->listRepository->userListsPaginated($user->id, $request->all());

        return ClientListResource::collection($itensList);
    }
}
