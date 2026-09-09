<?php

namespace App\Http\Controllers;

use App\Actions\Event\CheckAllEventsAction;
use App\Actions\Event\UpdateEventAction;
use App\Http\Requests\Event\EventCheckAllRequest;
use App\Http\Requests\Event\EventUpdateRequest;
use App\Models\Event;

class EventController extends Controller
{
    public function update(EventUpdateRequest $request, Event $event, UpdateEventAction $action)
    {
        $this->authorize('update', $event);
        return $action->execute($request, $event);
    }

    public function checkAll(EventCheckAllRequest $request, CheckAllEventsAction $action)
    {
        return $action->execute($request);
    }
}
