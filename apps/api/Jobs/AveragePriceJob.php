<?php

namespace App\Jobs;

use App\Models\CompanyProducts;
use App\Models\Product;
use App\Models\UserAddedProducts;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AveragePriceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $average_date_limit = Carbon::now()->subWeeks(Product::AVERAGE_PRICE_PURCHASE_DATE_LIMIT_WEEKS);

        $products = Product::whereHas('userAddedProducts', function ($query) use ($average_date_limit) {
            $query->where('purchase_date', '>', $average_date_limit)
                ->where('price', '>', 0)
                ->whereNotNull('company_id');
        })
            ->with('userAddedProducts', function ($query) use ($average_date_limit) {
                $query->where('purchase_date', '>', $average_date_limit)
                    ->where('price', '>', 0)
                    ->whereNotNull('company_id');
            })
            ->get();

        foreach ($products as $product) {
            $purchases = $product->userAddedProducts;

            if ($purchases->isEmpty()) {
                continue;
            }

            DB::transaction(function () use ($product, $purchases) {
                $product->average_price = $purchases->avg('price');
                $product->save();

                $purchasesByCompany = $purchases->groupBy('company_id');
                $companyIds = $purchasesByCompany->keys();

                CompanyProducts::where('product_id', $product->id)
                    ->whereNotIn('company_id', $companyIds)
                    ->update(['average_price' => null]);

                foreach ($purchasesByCompany as $companyId => $companyPurchases) {
                    CompanyProducts::updateOrCreate([
                        'company_id' => $companyId,
                        'product_id' => $product->id,
                    ], [
                        'average_price' => $companyPurchases->avg('price'),
                    ]);
                }
            });
        }

        UserAddedProducts::where('processed', false)
            ->update([
                'processed' => true
            ]);

        Log::info('Average Price Job Proceeded With Sucess!');
    }

    public function setAveragePrice(array $added_products)
    {
        $product_ids = [];

        foreach ($added_products as $products) {
            if (count($products) > 1) {
                $prices = array_column($products, 'average_price');
                $sum = array_sum($prices);

                $average = $sum / count($products);

                $product_ids[] = [
                    'id' => $products[0]['id'],
                    'average_price' => $average
                ];
            } else {
                $average = $products[0]['average_price'];

                $product_ids[] = [
                    'id' => $products[0]['id'],
                    'average_price' => $average
                ];
            }
        }

        return $product_ids;
    }
}
