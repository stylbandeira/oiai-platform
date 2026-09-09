<?php

namespace App\Http\Controllers;

use App\Actions\User\DashboardDataUserAction;
use App\Actions\User\DestroyUserAction;
use App\Actions\User\ExportUserAction;
use App\Actions\User\IndexUserAction;
use App\Actions\User\RevertDestroyUserAction;
use App\Actions\User\ShowUserAction;
use App\Actions\User\StoreUserAction;
use App\Actions\User\UpdateUserAction;
use App\Exports\Mappers\UserExportMapper;
use App\Http\Requests\User\IndexUserRequest;
use App\Http\Requests\User\UserStoreRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Resources\AdminUserResource;
use App\Http\Resources\ClientDashboardResource;
use App\Http\Resources\ClientUserResource;
use App\Http\Resources\CompanyUserResource;
use App\Models\User;
use App\Services\CompanyOwners\CompanyOwnerService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class);
    }

    public function dashboardData(Request $request, DashboardDataUserAction $action)
    {
        $this->authorize('dashboardData', $request->user());
        $user = $action->execute($request->user()->id);

        return response([
            'dashboardData' => new ClientDashboardResource($user),
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @param IndexUserRequest $request
     * @return void
     */
    public function index(IndexUserRequest $request, IndexUserAction $action)
    {
        $data = $request->validated();

        $data['with_trashed'] = $request->user()->isAdmin();

        $users = $action->execute($data);

        return AdminUserResource::collection($users);
    }

    /**
     * Store a new user.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(UserStoreRequest $request, StoreUserAction $action)
    {
        $user = $action->execute($request->user()->id, $request->validated());

        return response([
            'message' => 'Usuário criado com sucesso!',
            'user' => $user,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param integer $id
     * @param ShowUserAction $action
     * @return void
     */
    public function show(Request $request, User $user, ShowUserAction $action)
    {
        $currentUser = $request->user();

        $user = $action->execute($user->id);

        if ($currentUser->isAdmin()) {
            return new AdminUserResource($user);
        } else if ($currentUser->isClient() && $user->id === $currentUser->id) {
            return new ClientUserResource($user);
        } else if ($currentUser->isCompany() && $user->id === $currentUser->id) {
            return new CompanyUserResource($user);
        }

        return response([
            'message' => 'Não autorizado',
        ], 403);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UserUpdateRequest $request
     * @param User $user
     * @return void
     */
    public function update(UserUpdateRequest $request, User $user, UpdateUserAction $action)
    {
        $companies = $request->companies ?? [];

        $updatedUser = $action->execute($request->validated(), $user->id);

        $company_owner_service = app(CompanyOwnerService::class);

        if ($user->isCompany()) {
            $company_owner_service->sync($user, $companies, $request->user()->id);
        } else {
            $company_owner_service->detach($user);
        }

        return response([
            'message' => 'Usuário editado com sucesso!',
            'user' => new AdminUserResource($updatedUser),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return void
     */
    public function destroy(User $user, DestroyUserAction $action)
    {
        if (count($user->companies)) {
            return response([
                'message' => 'Apague a relação entre usuário e empresa primeiro.',
            ], 400);
        }

        if ($user->isAdmin()) {
            return response([
                'message' => 'Infelizmente não é possível deletar usuários do tipo admin.',
            ], 400);
        }

        $action->execute($user->id);

        return response([
            'message' => 'Usuário excluído com sucesso!',
        ]);
    }

    /**
     * Função para reverter deleção de usuário
     *
     * @param User $user
     * @return void
     */
    public function revertDestroy(User $user, RevertDestroyUserAction $action)
    {
        if (!$user->deleted_at) {
            return response([
                'message' => 'Usuário não precisa ser reativado.',
            ], 400);
        }

        $revertedUser = $action->execute($user);

        return response([
            'message' => 'Usuário revertido',
            'user' => new AdminUserResource($revertedUser),
        ]);
    }

    /**
     * Export users to CSV
     */
    public function export(
        IndexUserRequest $request,
        ExportService $exportService,
        UserExportMapper $mapper,
        ExportUserAction $action

    ) {
        return $action->execute($request, $exportService, $mapper);
    }
}
