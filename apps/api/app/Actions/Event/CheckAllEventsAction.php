<?php

namespace App\Actions\Event;

use App\Http\Requests\Event\EventCheckAllRequest;
use App\Repositories\EventRepository;
use Illuminate\Support\Facades\DB;

class CheckAllEventsAction
{
    public function __construct(private EventRepository $eventRepository) {}

    public function execute(EventCheckAllRequest $request)
    {
        DB::transaction(function () use ($request) {
            $this->eventRepository->markAllAsCheckedForUser($request->user());
        });

        return response([
            'message' => 'Todas as notificações marcadas como lidas.',
        ]);
    }
}
