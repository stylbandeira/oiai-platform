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
     * @param User $user
     * @param [type] $action
     * @param [type] $entity
     * @return boolean
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
     * @param User $user
     * @param [type] $action
     * @param [type] $entity
     * @return boolean
     */
    protected function canPerfomActionAsCompany(User $user, $action, $entity)
    {
        // Exemplo: empresa só pode editar seus próprios produtos
        if ($entity instanceof Product) {
            return $user->id === $entity->company->user_id && in_array($action, ['view', 'update', 'create']);
        }
    }

    /**
     * Define as ações que um cliente pode fazer
     *
     * @param User $user
     * @param [type] $action
     * @param [type] $entity
     * @return boolean
     */
    protected function canPerformActionAsClient(User $user, $action, $entity)
    {
        // Exemplo: cliente só pode visualizar produtos e empresas
        if ($entity instanceof Product) {
            return in_array($action, ['view']);
        }
    }
}
