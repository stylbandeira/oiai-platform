<?php

namespace Tests\Feature;

use App\Contracts\Product\ProductCombinationOptimizer;
use App\Models\Company;
use App\Models\CompanyProducts;
use App\Models\ItensList;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ShoppingListRequirement;
use App\Models\Unity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCombinationOptimizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_compares_distinct_eans_by_base_quantity_and_selects_cheapest_combination(): void
    {
        $gram = Unity::query()->updateOrCreate(
            ['abbreviation' => 'g'],
            ['name' => 'grama', 'dimension' => 'mass', 'convertion_factor' => 1],
        );
        $kilogram = Unity::query()->updateOrCreate(
            ['abbreviation' => 'kg'],
            ['name' => 'quilograma', 'dimension' => 'mass', 'convertion_factor' => 1000, 'base_unity_id' => $gram->id],
        );
        $liter = Unity::query()->updateOrCreate(
            ['abbreviation' => 'l'],
            ['name' => 'litro', 'dimension' => 'volume', 'convertion_factor' => 1000],
        );
        $type = ProductType::create(['name' => 'Arroz', 'normalized_name' => 'arroz']);
        $company = Company::factory()->create();

        $halfKilo = Product::factory()->create([
            'name' => 'Arroz A 500g', 'ean' => '7890000000001', 'product_type_id' => $type->id,
            'unit_id' => $gram->id, 'quantity' => 500,
        ]);
        $oneKilo = Product::factory()->create([
            'name' => 'Arroz B 1kg', 'ean' => '7890000000002', 'product_type_id' => $type->id,
            'unit_id' => $kilogram->id, 'quantity' => 1,
        ]);
        $volumeProduct = Product::factory()->create([
            'name' => 'Arroz líquido 1l', 'ean' => '7890000000003', 'product_type_id' => $type->id,
            'unit_id' => $liter->id, 'quantity' => 1,
        ]);

        $halfKiloOffer = CompanyProducts::create([
            'product_id' => $halfKilo->id, 'company_id' => $company->id, 'current_price' => 5,
        ]);
        $oneKiloOffer = CompanyProducts::create([
            'product_id' => $oneKilo->id, 'company_id' => $company->id, 'current_price' => 8,
        ]);
        $volumeOffer = CompanyProducts::create([
            'product_id' => $volumeProduct->id, 'company_id' => $company->id, 'current_price' => 1,
        ]);

        $list = ItensList::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Compras', 'favorite' => false, 'total' => 0,
        ]);
        $requirement = ShoppingListRequirement::create([
            'list_id' => $list->id,
            'product_type_id' => $type->id,
            'desired_quantity' => 5,
            'desired_unit_id' => $kilogram->id,
        ]);

        $result = app(ProductCombinationOptimizer::class)->optimize(
            $requirement,
            collect([$halfKiloOffer, $oneKiloOffer, $volumeOffer]),
        );

        $this->assertNotSame($halfKilo->ean, $oneKilo->ean);
        $this->assertNotSame($halfKilo->id, $oneKilo->id);
        $this->assertSame(0.01, $halfKiloOffer->fresh()->price_per_base_unit);
        $this->assertSame(0.008, $oneKiloOffer->fresh()->price_per_base_unit);
        $this->assertTrue($result->found());
        $this->assertSame($oneKiloOffer->id, $result->items[0]['offer_id']);
        $this->assertSame(5, $result->items[0]['packages']);
        $this->assertSame(5000.0, $result->desiredBaseQuantity);
        $this->assertSame(5000.0, $result->suppliedBaseQuantity);
        $this->assertSame(40.0, $result->totalPrice);
        $this->assertNotSame($volumeOffer->id, $result->items[0]['offer_id']);
    }

    public function test_does_not_match_mass_requirement_with_volume_offer(): void
    {
        $kilogram = Unity::query()->updateOrCreate(
            ['abbreviation' => 'kg'],
            ['name' => 'quilograma', 'dimension' => 'mass', 'convertion_factor' => 1000],
        );
        $liter = Unity::query()->updateOrCreate(
            ['abbreviation' => 'l'],
            ['name' => 'litro', 'dimension' => 'volume', 'convertion_factor' => 1000],
        );
        $type = ProductType::create(['name' => 'Produto genérico', 'normalized_name' => 'produto generico']);
        $product = Product::factory()->create([
            'product_type_id' => $type->id, 'unit_id' => $liter->id, 'quantity' => 1,
        ]);
        $offer = CompanyProducts::create([
            'product_id' => $product->id,
            'company_id' => Company::factory()->create()->id,
            'current_price' => 1,
        ]);
        $list = ItensList::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Compras', 'favorite' => false, 'total' => 0,
        ]);
        $requirement = ShoppingListRequirement::create([
            'list_id' => $list->id, 'product_type_id' => $type->id,
            'desired_quantity' => 5, 'desired_unit_id' => $kilogram->id,
        ]);

        $result = app(ProductCombinationOptimizer::class)->optimize($requirement, collect([$offer]));

        $this->assertFalse($result->found());
        $this->assertSame([], $result->items);
    }
}
