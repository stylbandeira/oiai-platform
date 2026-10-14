<?php

namespace App\Jobs;

use App\Enums\ProductQuantitySource;
use App\Models\Address;
use App\Models\Company;
use App\Models\CompanyProductCode;
use App\Models\CompanyProducts;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductReviewSuggestion;
use App\Models\Unity;
use App\Models\User;
use App\Models\UserAddedProducts;
use App\Repositories\UserRepository;
use App\Services\NotificationService;
use App\Services\Product\Gtin;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $unities;

    protected $not_inserted_products = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private NotificationService $notificationService)
    {
        $this->unities = Unity::all()->keyBy('abbreviation')->toArray();
    }

    public function handle()
    {
        $this->processPendingInvoices();
    }

    public function processPendingInvoices()
    {
        $invoices = Invoice::where('created_at', '>=', now()->subMonth())
            ->where('invoice_data', '!=', null)
            ->where('pending', 1)
            ->get();

        foreach ($invoices as $invoice) {
            $this->processInvoice($invoice);
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function processInvoice(Invoice $invoice)
    {
        $user_inserted_products = [];
        $invoice_data = json_decode($invoice->invoice_data);

        $products_data = $invoice_data->produtos;
        $user = User::find($invoice->user_id);

        Log::debug('Invoice data: ', [
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'products_count' => count($products_data),
            'invoice_data' => $invoice_data,
        ]);

        $rawDate = $invoice_data->dados_nota->data_emissao
            ?? $invoice_data->nota->data_emissao
            ?? null;

        try {
            if (! is_string($rawDate) || trim($rawDate) === '') {
                throw new \UnexpectedValueException('Data de emissão ausente na NFCe');
            }

            $format = preg_match('/[+-]\d{2}:\d{2}$/', $rawDate)
                ? 'd/m/Y H:i:sP'
                : 'd/m/Y H:i:s';
            $purchase_date = Carbon::createFromFormat($format, $rawDate);
        } catch (\Throwable $exception) {
            Log::warning('Não foi possível interpretar a data da NFCe', [
                'invoice_id' => $invoice->id,
                'data' => $rawDate,
                'error' => $exception->getMessage(),
            ]);

            $purchase_date = $invoice->receipt_data ? Carbon::parse($invoice->receipt_data) : null;
        }
        // FIRST OR CREATE DE COMPANY
        $company_data = $invoice_data->emitente;
        $company = Company::updateOrCreate(
            [
                'cnpj' => $company_data->cnpj,
                'ie' => $company_data->ie,
            ],
            [
                'name' => $company_data->razao_social,
                'cnpj' => $company_data->cnpj,
                'raw_address' => $company_data->endereco
                    .' - '.($company_data->numero ?? '')
                    .', '.$company_data->bairro
                    .', '.$company_data->municipio
                    .', '.$company_data->uf,
                'phone' => ($company_data->telefone ?? ''),
            ]
        );

        if ($company->wasChanged() || $company->wasRecentlyCreated) {
            $address = Address::firstOrCreate([
                'area' => $company_data->bairro,
                'city' => $company_data->municipio,
                'street' => $company_data->endereco,
                'number' => ($company_data->numero ?? ''),
            ], [
                'state' => $company_data->uf,
                'cep' => $company_data->cep ?? null,
            ]);

            $company->address_id = $address->id;
            $company->save();
        }

        foreach ($products_data as $productData) {
            $unity = $this->firstOrNewUnity($productData->unidade);
            $code = trim((string) ($productData->codigo ?? ''));
            $gtin = Gtin::normalize(isset($productData->ean) ? (string) $productData->ean : null);
            $merchantCode = $code !== ''
                ? CompanyProductCode::query()->where('company_id', $company->id)->where('code', $code)->first()
                : null;
            $globalProduct = $gtin ? Product::query()->where('ean', $gtin)->first() : null;
            $mappedProduct = $merchantCode?->product;

            // A valid GTIN wins over a recycled merchant code. Without a GTIN,
            // cProd is only meaningful within the issuing company.
            $insertedProduct = $globalProduct;
            if (! $insertedProduct && $mappedProduct && (
                $gtin === null || $mappedProduct->ean === $gtin ||
                ($mappedProduct->ean === null
                    && strcasecmp((string) $mappedProduct->raw_name, (string) $productData->descricao) === 0
                    && $mappedProduct->unit_id === $unity->id)
            )) {
                $insertedProduct = $mappedProduct;
                if ($gtin && ! $insertedProduct->ean) {
                    $insertedProduct->ean = $gtin;
                    $insertedProduct->save();
                }
            }

            $newProduct = $insertedProduct === null;
            if ($newProduct) {
                $quantitySource = ProductQuantitySource::DefaultExtraction;
                $insertedProduct = Product::create([
                    'sku' => 'AUTO-'.Str::uuid(),
                    'ean' => $gtin,
                    'description' => $productData->descricao,
                    'name' => $productData->descricao,
                    'raw_name' => $productData->descricao,
                    'normalized_name' => $productData->descricao,
                    'search_description' => $productData->descricao,
                    'quantity_source' => $quantitySource->value,
                    'quantity_dimension' => $unity->dimension ?? 'unit',
                    'quantity_confidence' => $quantitySource->confidence(),
                    'unit_id' => $unity->id,
                    'quantity' => 1,
                    'created_by' => $user->id,
                ]);
            }

            if ($code !== '') {
                CompanyProductCode::updateOrCreate(
                    ['company_id' => $company->id, 'code' => $code],
                    ['product_id' => $insertedProduct->id],
                );
            }

            if ($newProduct && ! $gtin) {
                $candidates = Product::query()
                    ->where('unit_id', $unity->id)
                    ->where('id', '!=', $insertedProduct->id)
                    ->where(function ($query) use ($productData): void {
                        $query->where('raw_name', $productData->descricao)
                            ->orWhere('name', $productData->descricao);
                    })
                    ->limit(3)
                    ->get();

                foreach ($candidates as $candidate) {
                    ProductReviewSuggestion::firstOrCreate(
                        [
                            'product_id' => $insertedProduct->id,
                            'suggested_product_id' => $candidate->id,
                            'type' => 'equivalence',
                            'status' => 'pending',
                        ],
                        ['submitted_by' => $user->id, 'original_name' => $productData->descricao],
                    );
                }
            }

            if (! $newProduct) {
                $insertedProduct->mentioned_quantity++;
                $insertedProduct->save();
            }

            try {
                $company_product = CompanyProducts::firstOrCreate(
                    [
                        'product_id' => $insertedProduct->id,
                        'company_id' => $company->id,
                    ],
                    [
                        'average_price' => null,
                    ]
                );

                $user_inserted_products[] = [
                    'user_id' => $user->id,
                    'price' => $productData->valor_unitario ?? 0,
                    'company_id' => $company->id,
                    'product_id' => $insertedProduct->id,
                    'created_at' => Carbon::now(),
                    'company_product_id' => $company_product->id,
                    'purchase_date' => $purchase_date,
                ];
            } catch (\Throwable $th) {
                $this->not_inserted_products[] = [
                    'product_error',
                    'product_data' => $productData,
                    'company' => $company,
                ];
            }
        }

        try {
            UserAddedProducts::insert($user_inserted_products);
        } catch (\Throwable $th) {
            Log::alert([
                'error' => 'UserAddedProducts not working',
                'message' => $th->getMessage(),
            ]);
        }

        $userRepo = new UserRepository($user);
        $userRepo->addPoints($user->id, count($products_data));

        try {
            $this->notificationService->createProductInsertionEvent($user, count($products_data), $company);
        } catch (\Throwable $th) {
            Log::alert('Problema com criação de evento de inserção de produtos');
        }

        $invoice->pending = false;
        $invoice->save();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Falha ao processar pedido: '.$exception->getMessage(), $this->not_inserted_products);
    }

    private function firstOrNewUnity(string $abbreviation): Unity
    {
        $abbreviation = strtolower(trim($abbreviation));
        $unity = Unity::where('abbreviation', $abbreviation)->first();

        if (! $unity) {
            $unity = new Unity;
            $unity->abbreviation = $abbreviation;
            $unity->name = $abbreviation;
            $unity->save();
        }

        return $unity;
    }
}
