<?php

namespace App\Services;

use SimpleXMLElement;
use Illuminate\Support\Facades\Log;
use Exception;

class NFCeXMLParserService
{
    /**
     * Processa o XML da NFCe
     */
    public function parseXML($xmlContent)
    {
        try {
            // Remover caracteres nulos e limpar XML
            $xmlContent = $this->cleanXML($xmlContent);

            // Carregar XML
            $xml = simplexml_load_string($xmlContent);

            if (!$xml) {
                throw new Exception('Não foi possível carregar XML');
            }

            // Extrair dados do XML
            $dados = $this->extractDataFromXML($xml);

            return [
                'status' => 'success',
                'data' => $dados
            ];
        } catch (Exception $e) {
            Log::error('Erro ao processar XML NFCe:', [
                'error' => $e->getMessage(),
                'xml_preview' => substr($xmlContent, 0, 500)
            ]);

            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Limpa o XML
     */
    private function cleanXML($xmlContent)
    {
        // Remove caracteres nulos
        $xmlContent = str_replace("\0", '', $xmlContent);

        // Remove a declaração de stylesheet se existir
        $xmlContent = preg_replace('/<\?xml-stylesheet[^>]+\?>/', '', $xmlContent);

        // Remove tags nulas no final
        $xmlContent = preg_replace('/null<\/nfeProc>$/', '</nfeProc>', $xmlContent);

        return trim($xmlContent);
    }

    /**
     * Extrai dados do XML
     */
    private function extractDataFromXML(SimpleXMLElement $xml)
    {
        // A resposta da SEFAZ-PE envolve o nfeProc oficial em outro nfeProc.
        // local-name() também suporta XMLs equivalentes que omitem ou alteram o
        // prefixo do namespace sem deixar de localizar a NFe.
        $nfe = $xml->xpath('//*[local-name()="NFe"]');

        if (empty($nfe)) {
            throw new Exception('Nó NFe não encontrado no XML');
        }

        $nfe = $nfe[0];
        $nfeNamespace = $nfe->getNamespaces(true)[''] ?? null;
        $nfeChildren = $nfeNamespace ? $nfe->children($nfeNamespace) : $nfe;
        $infNFe = $nfeChildren->infNFe[0];

        if (!isset($infNFe)) {
            throw new Exception('Nó infNFe não encontrado no XML');
        }

        // Extrair dados
        $dados = [
            'emitente' => $this->extractEmitente($infNFe),
            'destinatario' => $this->extractDestinatario($infNFe),
            'nota' => $this->extractDadosNota($infNFe),
            'produtos' => $this->extractProdutos($infNFe),
            'pagamento' => $this->extractPagamento($infNFe),
            'total' => $this->extractTotal($infNFe),
            'informacoes_adicionais' => $this->extractInformacoesAdicionais($infNFe),
            'chave_acesso' => (string) $infNFe->attributes()['Id'],
            'protocolo' => $this->extractProtocolo($xml),
        ];

        return $dados;
    }

    /**
     * Extrai dados do emitente
     */
    private function extractEmitente($infNFe)
    {
        $emitente = [
            'razao_social' => '',
            'cnpj' => '',
            'endereco' => '',
            'numero' => '',
            'bairro' => '',
            'municipio' => '',
            'uf' => '',
            'cep' => '',
            'telefone' => '',
            'ie' => '',
            'crt' => '',
        ];

        if (isset($infNFe->emit)) {
            $emit = $infNFe->emit;

            $emitente['cnpj'] = (string) $emit->CNPJ;
            $emitente['razao_social'] = (string) $emit->xNome;
            $emitente['ie'] = (string) $emit->IE;
            $emitente['crt'] = (string) $emit->CRT;

            if (isset($emit->enderEmit)) {
                $ender = $emit->enderEmit;
                $emitente['endereco'] = (string) $ender->xLgr;
                $emitente['numero'] = (string) $ender->nro;
                $emitente['bairro'] = (string) $ender->xBairro;
                $emitente['municipio'] = (string) $ender->xMun;
                $emitente['uf'] = (string) $ender->UF;
                $emitente['cep'] = $this->formatCEP((string) $ender->CEP);
            }
        }

        return $emitente;
    }

    /**
     * Extrai dados do destinatário
     */
    private function extractDestinatario($infNFe)
    {
        $destinatario = [
            'nome' => 'CONSUMIDOR FINAL',
            'cpf' => '',
            'cnpj' => '',
        ];

        if (isset($infNFe->dest)) {
            $dest = $infNFe->dest;

            if (isset($dest->CPF)) {
                $destinatario['cpf'] = $this->formatCPF((string) $dest->CPF);
                $destinatario['nome'] = 'CONSUMIDOR FINAL (CPF: ' . $destinatario['cpf'] . ')';
            } elseif (isset($dest->CNPJ)) {
                $destinatario['cnpj'] = $this->formatCNPJ((string) $dest->CNPJ);
                $destinatario['nome'] = 'CONSUMIDOR FINAL (CNPJ: ' . $destinatario['cnpj'] . ')';
            }
        }

        return $destinatario;
    }

    /**
     * Extrai dados da nota
     */
    private function extractDadosNota($infNFe)
    {
        $nota = [
            'numero' => '',
            'serie' => '',
            'modelo' => '',
            'data_emissao' => '',
            'natureza_operacao' => '',
            'codigo_municipio' => '',
            'forma_pagamento' => '',
            'valor_total' => 0,
        ];

        if (isset($infNFe->ide)) {
            $ide = $infNFe->ide;

            $nota['numero'] = (string) $ide->nNF;
            $nota['serie'] = (string) $ide->serie;
            $nota['modelo'] = (string) $ide->mod;
            $nota['data_emissao'] = $this->formatDateTime((string) $ide->dhEmi);
            $nota['natureza_operacao'] = (string) $ide->natOp;
            $nota['codigo_municipio'] = (string) $ide->cMunFG;
        }

        if (isset($infNFe->total->ICMSTot)) {
            $total = $infNFe->total->ICMSTot;
            $nota['valor_total'] = (float) $total->vNF;
        }

        // Determinar forma de pagamento
        if (isset($infNFe->pag->detPag)) {
            $pag = $infNFe->pag->detPag;
            $tPag = (string) $pag->tPag;

            $formasPagamento = [
                '01' => 'Dinheiro',
                '02' => 'Cheque',
                '03' => 'Cartão de Crédito',
                '04' => 'Cartão de Débito',
                '05' => 'Crédito Loja',
                '10' => 'Vale Alimentação',
                '11' => 'Vale Refeição',
                '12' => 'Vale Presente',
                '13' => 'Vale Combustível',
                '15' => 'Boleto Bancário',
                '90' => 'Sem pagamento',
                '99' => 'Outros',
            ];

            $nota['forma_pagamento'] = $formasPagamento[$tPag] ?? 'Desconhecido';
        }

        return $nota;
    }

    /**
     * Extrai produtos
     */
    private function extractProdutos($infNFe)
    {
        $produtos = [];

        if (!isset($infNFe->det)) {
            return $produtos;
        }

        foreach ($infNFe->det as $det) {
            $prod = $det->prod;

            $produto = [
                'item' => (int) $det['nItem'],
                'codigo' => (string) $prod->cProd,
                'ean' => (string) $prod->cEAN,
                'descricao' => (string) $prod->xProd,
                'ncm' => (string) $prod->NCM,
                'cfop' => (string) $prod->CFOP,
                'unidade' => (string) $prod->uCom,
                'quantidade' => (float) $prod->qCom,
                'valor_unitario' => (float) $prod->vUnCom,
                'valor_total' => (float) $prod->vProd,
                'valor_desconto' => isset($prod->vDesc) ? (float) $prod->vDesc : 0,
                'informacoes_adicionais' => isset($det->infAdProd) ? (string) $det->infAdProd : '',
            ];

            $produtos[] = $produto;
        }

        return $produtos;
    }

    /**
     * Extrai pagamento
     */
    private function extractPagamento($infNFe)
    {
        $pagamentos = [];

        if (!isset($infNFe->pag->detPag)) {
            return $pagamentos;
        }

        $pag = $infNFe->pag->detPag;

        $pagamento = [
            'forma' => $this->getFormaPagamento((string) $pag->tPag),
            'valor' => (float) $pag->vPag,
            'data' => isset($pag->dPag) ? (string) $pag->dPag : '',
        ];

        // Se for cartão, extrair informações adicionais
        if (isset($pag->card)) {
            $card = $pag->card;
            $pagamento['cartao'] = [
                'bandeira' => $this->getBandeiraCartao((string) $card->tBand),
                'cnpj' => (string) $card->CNPJ,
                'autorizacao' => (string) $card->cAut,
            ];
        }

        $pagamentos[] = $pagamento;

        return $pagamentos;
    }

    /**
     * Extrai totais
     */
    private function extractTotal($infNFe)
    {
        $total = [
            'valor_produtos' => 0,
            'valor_desconto' => 0,
            'valor_frete' => 0,
            'valor_total' => 0,
            'valor_tributos' => 0,
            'base_calculo_icms' => 0,
            'valor_icms' => 0,
            'valor_pis' => 0,
            'valor_cofins' => 0,
        ];

        if (isset($infNFe->total->ICMSTot)) {
            $icmsTot = $infNFe->total->ICMSTot;

            $total['valor_produtos'] = (float) $icmsTot->vProd;
            $total['valor_desconto'] = (float) $icmsTot->vDesc;
            $total['valor_frete'] = (float) $icmsTot->vFrete;
            $total['valor_total'] = (float) $icmsTot->vNF;
            $total['valor_tributos'] = (float) $icmsTot->vTotTrib;
            $total['base_calculo_icms'] = (float) $icmsTot->vBC;
            $total['valor_icms'] = (float) $icmsTot->vICMS;

            if (isset($icmsTot->vPIS)) {
                $total['valor_pis'] = (float) $icmsTot->vPIS;
            }

            if (isset($icmsTot->vCOFINS)) {
                $total['valor_cofins'] = (float) $icmsTot->vCOFINS;
            }
        }

        return $total;
    }

    /**
     * Extrai informações adicionais
     */
    private function extractInformacoesAdicionais($infNFe)
    {
        $info = [
            'observacoes' => '',
            'responsavel_tecnico' => [],
        ];

        if (isset($infNFe->infAdic->infCpl)) {
            $info['observacoes'] = (string) $infNFe->infAdic->infCpl;
        }

        if (isset($infNFe->infRespTec)) {
            $respTec = $infNFe->infRespTec;
            $info['responsavel_tecnico'] = [
                'cnpj' => (string) $respTec->CNPJ,
                'contato' => (string) $respTec->xContato,
                'email' => (string) $respTec->email,
                'telefone' => (string) $respTec->fone,
            ];
        }

        return $info;
    }

    /**
     * Extrai protocolo de autorização
     */
    private function extractProtocolo($xml)
    {
        $protocolo = [
            'numero' => '',
            'data_recebimento' => '',
            'status' => '',
            'motivo' => '',
        ];

        // Tentar pegar do protNFe, inclusive no envelope específico da SEFAZ-PE.
        $prot = $xml->xpath('//*[local-name()="protNFe"]');

        if (!empty($prot)) {
            $prot = $prot[0];
            $protNamespace = $prot->getNamespaces(true)[''] ?? null;
            $protChildren = $protNamespace ? $prot->children($protNamespace) : $prot;
            $infProt = $protChildren->infProt;

            $protocolo['numero'] = (string) $infProt->nProt;
            $protocolo['data_recebimento'] = $this->formatDateTime((string) $infProt->dhRecbto);
            $protocolo['status'] = (string) $infProt->cStat;
            $protocolo['motivo'] = (string) $infProt->xMotivo;
        }

        return $protocolo;
    }

    /**
     * Formata CEP
     */
    private function formatCEP($cep)
    {
        if (strlen($cep) === 8) {
            return substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
        }
        return $cep;
    }

    /**
     * Formata CPF
     */
    private function formatCPF($cpf)
    {
        if (strlen($cpf) === 11) {
            return substr($cpf, 0, 3) . '.' .
                substr($cpf, 3, 3) . '.' .
                substr($cpf, 6, 3) . '-' .
                substr($cpf, 9, 2);
        }
        return $cpf;
    }

    /**
     * Formata CNPJ
     */
    private function formatCNPJ($cnpj)
    {
        if (strlen($cnpj) === 14) {
            return substr($cnpj, 0, 2) . '.' .
                substr($cnpj, 2, 3) . '.' .
                substr($cnpj, 5, 3) . '/' .
                substr($cnpj, 8, 4) . '-' .
                substr($cnpj, 12, 2);
        }
        return $cnpj;
    }

    /**
     * Formata data/hora
     */
    private function formatDateTime($dateTime)
    {
        try {
            $date = new \DateTime($dateTime);
            return $date->format('d/m/Y H:i:s');
        } catch (\Exception $e) {
            return $dateTime;
        }
    }

    /**
     * Obtém forma de pagamento pelo código
     */
    private function getFormaPagamento($codigo)
    {
        $formas = [
            '01' => 'Dinheiro',
            '02' => 'Cheque',
            '03' => 'Cartão de Crédito',
            '04' => 'Cartão de Débito',
            '05' => 'Crédito Loja',
            '10' => 'Vale Alimentação',
            '11' => 'Vale Refeição',
            '12' => 'Vale Presente',
            '13' => 'Vale Combustível',
            '15' => 'Boleto Bancário',
            '90' => 'Sem pagamento',
            '99' => 'Outros',
        ];

        return $formas[$codigo] ?? 'Desconhecido';
    }

    /**
     * Obtém bandeira do cartão pelo código
     */
    private function getBandeiraCartao($codigo)
    {
        $bandeiras = [
            '01' => 'Visa',
            '02' => 'MasterCard',
            '03' => 'American Express',
            '04' => 'Sorocred',
            '05' => 'Diners Club',
            '06' => 'Elo',
            '07' => 'Hipercard',
            '08' => 'Aura',
            '09' => 'Cabal',
            '99' => 'Outros',
        ];

        return $bandeiras[$codigo] ?? 'Desconhecido';
    }
}
