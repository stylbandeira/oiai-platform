<?php

namespace App\Actions\Event;

use App\Http\Requests\Event\EventUpdateRequest;
use App\Models\Event;
use App\Repositories\EventRepository;

class UpdateEventAction
{
    public function __construct(private EventRepository $eventRepository) {}

    public function execute(EventUpdateRequest $request, Event $event)
    {
        $this->eventRepository->update($event->id, $request->validated());

        return response(['message' => 'Evento alterado com sucesso!']);
    }
}
