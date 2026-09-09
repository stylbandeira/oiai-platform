<?php

namespace App\Observers;

use App\Models\ItensList;

class ListObserver
{
    /**
     * Handle the ItensList "created" event.
     */
    public function created(ItensList $itensList): void
    {
        //
    }

    /**
     * Handle the ItensList "updated" event.
     */
    public function updated(ItensList $itensList): void
    {
        //
    }

    /**
     * Handle the ItensList "deleted" event.
     */
    public function deleted(ItensList $itensList): void
    {
        $itensList->listProducts()->delete();
    }

    /**
     * Handle the ItensList "restored" event.
     */
    public function restored(ItensList $itensList): void
    {
        //
    }

    /**
     * Handle the ItensList "force deleted" event.
     */
    public function forceDeleted(ItensList $itensList): void
    {
        //
    }
}
