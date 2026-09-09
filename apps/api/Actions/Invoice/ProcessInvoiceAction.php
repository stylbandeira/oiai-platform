<?php

namespace App\Actions\Invoice;

use App\Http\Requests\Invoice\ProcessInvoiceRequest;
use App\Models\Invoice;
use App\Services\Invoice\InvoiceService;
use App\Services\NFCeScraperService;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProcessInvoiceAction
{
    public function __construct(
        private NFCeScraperService $scraper,
        private InvoiceService $invoiceService,
    ) {}

    public function execute(ProcessInvoiceRequest $request)
    {
        $user = Auth::user();
        $qrData = $request->input('qr_code_data');
        $accessKey = $request->invoice_code
            ? $this->invoiceService->normalizeAccessKey($request->invoice_code)
            : null;
        $source = $accessKey ?? $qrData;
        $result = $this->scraper->scrapeFromQRCode($source);

        if (($result['status'] ?? 'error') === 'error') {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Erro ao tentar capturar dados da NFCe',
                'qr_data' => $source,
            ], 400);
        }

        Log::debug('Dados capturados da NFCe', [
            'provider_type' => $result['tipo'] ?? null,
            'url_consulta' => $result['url_consulta'] ?? null,
            'data' => $result['data'] ?? [],
        ]);

        $invoiceData = $result['data'];
        $receiptData = $invoiceData['protocolo']['data_recebimento']
            ?? $invoiceData['dados_nota']['data_emissao']
            ?? null;
        $invoiceCode = ($accessKey ? 'NFCe' . $accessKey : null) ?? $invoiceData['chave_acesso'];

        $invoice = Invoice::firstOrCreate(
            [
                'access_key' => $invoiceCode,
            ],
            [
                'user_id' => $user->id,
                'receipt_data' => $receiptData ? DateTime::createFromFormat('d/m/Y H:i:s', $receiptData) : Carbon::today(),
                'invoice_data' => json_encode($invoiceData),
                'pending' => true,
            ]
        );

        if (!$invoice->wasRecentlyCreated) {
            return response([
                'success' => true,
                'message' => 'NFCe já cadastrada',
            ], 409);
        }

        return response([
            'success' => true,
            'message' => 'NFCe processada com sucesso',
            'invoice' => $invoice,
        ]);
    }
}
