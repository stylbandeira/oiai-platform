<?php

namespace App\Http\Controllers;

use App\Actions\Company\DashboardDataCompanyAction;
use App\Actions\Company\DestroyCompanyAction;
use App\Actions\Company\IndexCompanyAction;
use App\Actions\Company\ShowCompanyAction;
use App\Actions\Company\StoreCompanyAction;
use App\Actions\Company\UpdateCompanyAction;
use App\Http\Requests\Company\CompanyStoreRequest;
use App\Http\Requests\Company\CompanyUpdateRequest;
use App\Http\Resources\AdminCompanyResource;
use App\Http\Resources\ClientCompanyResource;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Company::class, 'company');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, IndexCompanyAction $action)
    {
        return $action->execute($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CompanyStoreRequest $request, StoreCompanyAction $action)
    {
        return $action->execute($request);
    }

    /**
     * Display the specified company.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Company $company, ShowCompanyAction $action)
    {
        $user = $request->user();

        $company = $action->execute($user, $company);

        if ($user->isAdmin()) {
            return new AdminCompanyResource($company);
        }

        if ($user->isCompany()) {
            return new CompanyResource($company);
        }

        return new ClientCompanyResource($company);
    }

    public function dashboardData(Company $company, DashboardDataCompanyAction $action)
    {
        $this->authorize('view', $company);

        return $action->execute($company);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function update(CompanyUpdateRequest $request, Company $company, UpdateCompanyAction $action)
    {
        return $action->execute($request, $company);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company, DestroyCompanyAction $action)
    {
        return $action->execute($company);
    }
}
