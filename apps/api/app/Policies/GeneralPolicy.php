<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GeneralPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     * Para usar a Policy, use-a no construct do Controller
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Verifica se um usuário pode efetuar uma determinada ação em uma determinada entidade
     *
     * @param string $action
     * @param object $entity
     * @return bool
     */
    public function canPerformAction(User $user, $action, $entity)
    {
        switch ($user->type) {
            case 'admin':
                return true;
            case 'company':
                return $this->canPerfomActionAsCompany($user, $action, $entity);
            case 'client':
                return $this->canPerformActionAsClient($user, $action, $entity);
            default:
                return false;
        }
    }

    /**
     * Define as ações que uma empresa pode fazer
     *
     * @param string $action
     * @param object $entity
     * @return bool
     */
    protected function canPerfomActionAsCompany(User $user, $action, $entity)
    {
        // Exemplo: empresa só pode editar seus próprios produtos
        if (! $entity instanceof Product || ! in_array($action, ['view', 'update', 'create'])) {
            return false;
        }

        return $entity->companies()
            ->whereHas('owners', fn ($query) => $query->whereKey($user->id))
            ->exists();
    }

    /**
     * Define as ações que um cliente pode fazer
     *
     * @param string $action
     * @param object $entity
     * @return bool
     */
    protected function canPerformActionAsClient(User $user, $action, $entity)
    {
        // Exemplo: cliente só pode visualizar produtos e empresas
        return $entity instanceof Product && in_array($action, ['view']);
    }
}
