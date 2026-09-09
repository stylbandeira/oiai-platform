<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\Invoice;
use App\Models\ItensList;
use App\Models\Product;
use App\Models\User;
use App\Observers\EventObserver;
use App\Observers\InvoiceObserver;
use App\Observers\ListObserver;
use App\Observers\ProductObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        User::observe(UserObserver::class);
        Event::observe(EventObserver::class);
        Product::observe(ProductObserver::class);
        Invoice::observe(InvoiceObserver::class);
        ItensList::observe(ListObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
