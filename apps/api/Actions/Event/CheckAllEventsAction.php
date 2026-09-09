<?php

namespace App\Actions\Event;

use App\Http\Requests\Event\EventCheckAllRequest;
use App\Repositories\EventRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckAllEventsAction
{
    public function __construct(private EventRepository $eventRepository) {}
    public function execute(EventCheckAllRequest $request)
    {
        $notificationsIds = collect($request->validated()['notifications'])
            ->pluck('id')
            ->toArray();

        try {
            DB::transaction(function () use ($notificationsIds) {
                $this->eventRepository->updateAll($notificationsIds, ['checked' => true]);
            });
        } catch (\Throwable $th) {
            Log::alert($th);
        }

        return response([
            'message' => 'Todas as notificações marcadas como lidas.',
        ]);
    }
}
