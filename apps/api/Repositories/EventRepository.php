<?php

namespace App\Repositories;

use App\Models\Event;

class EventRepository
{
    protected Event $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function all()
    {
        return $this->event->all();
    }

    public function find($id)
    {
        return $this->event->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->event->create($data);
    }

    public function update(string $id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    public function updateAll(array $ids, array $data)
    {
        $events = Event::whereIn('id', $ids)
            ->update($data);

        return $events;
    }

    public function delete($id)
    {
        return $this->event->destroy($id);
    }
}
