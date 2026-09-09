<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddedProducts;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function getSystemStats(): array
    {
        return [
            'totalUsers' => User::count(),
            'totalCompanies' => Company::count(),
            'totalProducts' => Product::count(),
            'systemHealth' => $this->calculateSystemHealth()
        ];
    }

    public function getTopUsers(int $limit = 3): Collection
    {
        return $this->userRepository->getTopUsers($limit);
    }

    /**
     * Função a ser finalizada
     *
     * @return Collection
     */
    public function getTopMentionedStores(): array
    {
        return [];
    }

    public function getTopMentionedProducts(): array
    {
        $top_mentioned_products = Product::withCount(['userAddedProducts as registrations'])->get();
        $products = [];

        if (count($top_mentioned_products)) {
            foreach ($top_mentioned_products as $product) {
                $products[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'registrations' => $product->registrations,
                ];
            }
        }

        return $products;
    }

    private function calculateSystemHealth(): float
    {
        //INSERIR LÓGICA PARA CALCULAR A SAÚDE DO SISTEMA
        return 99.5;
    }
}
