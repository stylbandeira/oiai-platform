<?php

namespace App\Http\Controllers;

use App\Actions\Invoice\ProcessInvoiceAction;
use App\Actions\Invoice\ProcessXMLAction;
use App\Http\Requests\Invoice\ProcessInvoiceRequest;
use App\Http\Requests\Invoice\ProcessXMLRequest;
use App\Services\Invoice\InvoiceService;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoice_service
    ) {}

    /**
     * Endpoint específico para testar XML direto
     */
    public function processXML(ProcessXMLRequest $request, ProcessXMLAction $action)
    {
        return $action->execute($request);
    }

    /**
     * Processa o QRCode usando scrapper para obter um JSON com os dados da nota fiscal
     *
     * @param ProcessInvoiceRequest $request
     * @return void
     */
    public function processInvoice(ProcessInvoiceRequest $request, ProcessInvoiceAction $action)
    {
        if ($request->invoice_code && !$this->invoice_service->isValid($request->invoice_code)) {
            return response([
                'message' => 'Código de NFCe ainda não suportado.'
            ], 400);
        }

        return $action->execute($request);
    }
}
