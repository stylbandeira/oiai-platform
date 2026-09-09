<?php

namespace App\Services\NFCe;

use Exception;
use Symfony\Component\DomCrawler\Crawler;

class SantaCatarinaNFCeProvider extends AbstractNFCeProvider
{
    private const BASE_URL = 'https://sat.sef.sc.gov.br/nfce/consulta';
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            
    public function supports(string $qrData): bool
    {
        $host = strtolower(parse_url($qrData, PHP_URL_HOST) ?? '');
        if (in_array($host, ['sat.sef.sc.gov.br', 'hom.sat.sef.sc.gov.br'], true)) {
            return true;
        }

        $accessKey = $this->accessKey($qrData);

        return str_starts_with($accessKey, '42') && substr($accessKey, 20, 2) === '65';
    }

    public function buildUrl(string $qrData): string
    {
        if (filter_var($qrData, FILTER_VALIDATE_URL)) {
            return $qrData;
        }

        $parameter = str_contains($qrData, '|')
            ? $qrData
            : $this->accessKey($qrData).'|3|1';

        return self::BASE_URL.'?p='.rawurlencode($parameter);
    }

    public function scrapeFromQRCode(string $qrData): array
    {
        $url = $this->buildUrl($qrData);
        $response = $this->client->get($url);

        return $this->parseHtml($response->getBody()->getContents(), $url);
    }

    public function parseHtml(string $html, string $url): array
    {
        if (preg_match('/valida(?:ç|&ccedil;|&#231;)ão de segurança|SecurityVerify/iu', $html)) {
            throw new Exception('O portal da NFC-e de Santa Catarina solicitou validação de segurança.');
        }

        $crawler = new Crawler($html);
        if (strlen($crawler->text()) < 100) {
            throw new Exception('Página HTML de consulta da NFC-e catarinense vazia ou inválida.');
        }

        $data = [
            'emitente' => $this->issuer($crawler),
            'destinatario' => $this->recipient($crawler),
            'nota' => $this->invoice($crawler),
            'produtos' => $this->products($crawler),
            'pagamento' => $this->payments($crawler),
            'total' => $this->totals($crawler),
            'informacoes_adicionais' => [
                'observacoes' => $this->text($crawler, '#infos h4:contains("Informações de interesse do contribuinte") + ul li'),
                'informacao_fisco' => '',
                'informacao_contribuinte' => '',
            ],
        ];
        $data['dados_nota'] = $data['nota'];
        $data['protocolo'] = $this->protocol($crawler, $data['nota']['data_emissao']);
        $data['chave_acesso'] = $data['nota']['chave_acesso'];

        if ($data['chave_acesso'] === '' || $data['produtos'] === []) {
            throw new Exception('Página HTML de consulta da NFC-e catarinense vazia ou inválida.');
        }

        return [
            'status' => 'success',
            'message' => 'NFCe processada com sucesso (via HTML/SC)',
            'url_consulta' => $url,
            'tipo' => 'html',
            'data' => $data,
            'produtos_count' => count($data['produtos']),
            'valor_total' => $data['total']['valor_total'],
        ];
    }

    private function accessKey(string $qrData): string
    {
        if (filter_var($qrData, FILTER_VALIDATE_URL)) {
            parse_str(parse_url($qrData, PHP_URL_QUERY) ?? '', $query);
            $qrData = $query['p'] ?? '';
        }

        return preg_replace('/\D/', '', explode('|', $qrData)[0]) ?? '';
    }

    private function issuer(Crawler $crawler): array
    {
        $texts = $crawler->filter('.txtCenter')->first()->filter('.text');
        $cnpj = '';
        if ($texts->count() > 0 && preg_match('/CNPJ:\s*([\d.\/\-]+)/i', $texts->eq(0)->text(), $matches)) {
            $cnpj = preg_replace('/\D/', '', $matches[1]);
        }
        $address = $texts->count() > 1
            ? array_values(array_filter(array_map(fn (string $part) => $this->clean($part), explode(',', $texts->eq(1)->text()))))
            : [];

        return [
            'razao_social' => $this->text($crawler, '.txtCenter .txtTopo'),
            'cnpj' => $cnpj,
            'endereco' => $address[0] ?? '',
            'numero' => $address[1] ?? '',
            'bairro' => $address[2] ?? '',
            'municipio' => $address[3] ?? '',
            'uf' => $address[4] ?? 'SC',
            'cep' => '',
            'telefone' => '',
            'ie' => '',
        ];
    }

    private function recipient(Crawler $crawler): array
    {
        $consumer = $crawler->filterXPath('//*[@id="infos"]//h4[contains(normalize-space(.), "Consumidor")]/following-sibling::ul[1]');
        $text = $consumer->count() ? $consumer->text() : '';
        preg_match('/CPF:\s*([\d.\-]+)/i', $text, $document);
        preg_match('/Nome:\s*([^\r\n]+)/i', $text, $name);

        return [
            'nome' => isset($name[1]) ? $this->clean($name[1]) : 'CONSUMIDOR FINAL',
            'cpf_cnpj' => $document[1] ?? '',
            'endereco' => '',
        ];
    }

    private function invoice(Crawler $crawler): array
    {
        $text = $crawler->text();
        $totals = $this->totals($crawler);
        preg_match('/Número:\s*(\d+)/iu', $text, $number);
        preg_match('/Série:\s*(\d+)/iu', $text, $series);
        preg_match('/Emissão:\s*(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})/iu', $text, $issuedAt);

        return [
            'numero' => $number[1] ?? '',
            'serie' => $series[1] ?? '',
            'chave_acesso' => preg_replace('/\D/', '', $this->text($crawler, 'span.chave')),
            'data_emissao' => $issuedAt[1] ?? '',
            'data_entrada_saida' => '',
            'modelo' => 'NFC-e',
            'natureza_operacao' => '',
            'protocolo' => '',
            'valor_total' => $totals['valor_total'],
            'valor_desconto' => $totals['valor_desconto'],
            'valor_frete' => 0,
            'valor_seguro' => 0,
            'valor_outras_despesas' => 0,
        ];
    }

    private function products(Crawler $crawler): array
    {
        $products = [];
        $crawler->filter('#tabResult tr')->each(function (Crawler $row) use (&$products) {
            preg_match('/Código:\s*([^\s\)]+)/iu', $this->text($row, '.RCod'), $code);
            preg_match('/Qtde\.:\s*([\d.,]+)/iu', $this->text($row, '.Rqtd'), $quantity);
            preg_match('/UN:\s*(\S+)/iu', $this->text($row, '.RUN'), $unit);
            preg_match('/Vl\. Unit\.:\s*([\d.,]+)/iu', $this->text($row, '.RvlUnit'), $unitValue);
            $products[] = [
                'codigo' => $code[1] ?? '',
                'descricao' => $this->text($row, 'span.txtTit'),
                'ean' => '',
                'quantidade' => $this->number($quantity[1] ?? '0'),
                'unidade' => $unit[1] ?? '',
                'valor_unitario' => $this->number($unitValue[1] ?? '0'),
                'valor_total' => $this->number($this->text($row, 'span.valor')),
            ];
        });

        return $products;
    }

    private function payments(Crawler $crawler): array
    {
        $payments = [];
        $crawler->filter('#totalNota #linhaTotal')->each(function (Crawler $row) use (&$payments) {
            if (! $row->filter('label.tx')->count() || ! $row->filter('.totalNumb')->count()) {
                return;
            }
            $method = $this->text($row, 'label.tx');
            if (mb_strtolower($method) !== 'troco') {
                $payments[] = ['forma' => $method, 'valor' => $this->number($this->text($row, '.totalNumb'))];
            }
        });

        return $payments;
    }

    private function totals(Crawler $crawler): array
    {
        $totals = ['valor_produtos' => 0, 'valor_desconto' => 0, 'valor_frete' => 0, 'valor_total' => 0];
        $crawler->filter('#totalNota #linhaTotal')->each(function (Crawler $row) use (&$totals) {
            if (! $row->filter('label')->count() || ! $row->filter('.totalNumb')->count()) {
                return;
            }
            $label = mb_strtolower($this->text($row, 'label'));
            $value = $this->number($this->text($row, '.totalNumb'));
            if (str_contains($label, 'valor total')) {
                $totals['valor_produtos'] = $value;
            }
            if (str_contains($label, 'desconto')) {
                $totals['valor_desconto'] = $value;
            }
            if (str_contains($label, 'valor a pagar')) {
                $totals['valor_total'] = $value;
            }
        });

        return $totals;
    }

    private function protocol(Crawler $crawler, string $issuedAt): array
    {
        preg_match('/Protocolo de Autorização:\s*(\d+)\s+(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})/iu', $crawler->text(), $matches);

        return [
            'numero' => $matches[1] ?? '',
            'data_recebimento' => $matches[2] ?? $issuedAt,
            'status' => isset($matches[1]) ? 'Autorizada' : '',
            'motivo' => '',
        ];
    }

    private function text(Crawler $crawler, string $selector): string
    {
        return $crawler->filter($selector)->count() ? $this->clean($crawler->filter($selector)->first()->text()) : '';
    }

    private function clean(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function number(string $value): float
    {
        $value = preg_replace('/[^\d,.]/', '', $value);
        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }
}
