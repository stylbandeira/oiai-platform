<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Event;
use App\Models\User;
use App\Repositories\EventRepository;

class NotificationService
{
    public function __construct(private EventRepository $eventRepository) {}

    /**
     * TODO - Criar notificação para um usuário
     *
     * @return void
     */
    public function sendUserNotification(): void {}

    public function userOwnershipRequestActivated(User $user, Company $company)
    {
        $this->eventRepository->create([
            'user_id' => $user->id,
            'target_type' => 'company',
            'title' => 'company_ownership_active',
            'description' => 'Sua requisição para administrar a empresa ' . $company->name . ' foi  aceita.',
            'where' => '',
            'type' => 'company',
            'points' => 0,
            'link' => 'company_ownership_active',
            'entity_type' => 'user',
            'entity_id' => $user->id,
        ]);
    }

    public function createProductInsertionEvent(User $user, Int $quantity, Company $company)
    {
        $event = [];

        $event = [
            'user_id' => $user->id,
            'title' => Event::TYPE_PRODUCT_INSERT,
            'description' => ucwords(strtolower($user->name)) . ' adicionou ' . $quantity . ' produtos.',
            'where' => $company->name ?? '',
            'type' => 'client',
            'points' => $quantity,
            'link' => Event::TYPE_PRODUCT_INSERT,
        ];

        try {
            $this->eventRepository->create($event);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Create a event from when an user requests an access to an company account.
     *
     * @param User $user
     * @param Company $company
     * @return void
     */
    public function createOwnershipRequestEvent(User $user, Company $company)
    {
        $event = [];

        $event = [
            'user_id' => $user->id,
            'title' => Event::TYPE_COMPANY_OWNER_REQUEST,
            'description' => ucwords(strtolower($user->name)) . ' solicitou acesso de admin à empresa: ' . $company->name,
            'where' => $company->name ?? '',
            'type' => 'admin',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'points' => 0,
            'link' => Event::TYPE_COMPANY_OWNER_REQUEST,
            'target_type' => 'admin',
        ];

        try {
            $this->eventRepository->create($event);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
