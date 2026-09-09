<?php

namespace App\Actions\Invoice;

use App\Http\Requests\Invoice\ProcessXMLRequest;
use App\Services\NFCeXMLParserService;

class ProcessXMLAction
{
    public function __construct(private NFCeXMLParserService $xmlParser)
    {
    }

    public function execute(ProcessXMLRequest $request)
    {
        $result = $this->xmlParser->parseXML($request->input('xml_content'));

        if ($result['status'] === 'error') {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao tentar capturar dados da NFCe',
            ], 400);
        }

        return response([
            'success' => true,
            'message' => 'XML processado com sucesso',
        ]);
    }
}
