<?php

namespace App\Services\NFCe;

use App\Services\NFCeXMLParserService;
use Exception;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class PernambucoNFCeProvider extends AbstractNFCeProvider
{
    private const BASE_URL = 'https://nfce.sefaz.pe.gov.br/nfce/consulta';

    private const DFE_PORTAL_URL = 'https://dfe-portal.svrs.rs.gov.br/Dfe/ConsultaPublicaDfe';

    private NFCeXMLParserService $xmlParser;

    public function __construct(NFCeXMLParserService $xmlParser)
    {
        parent::__construct();

        $this->xmlParser = $xmlParser;
    }

    /**
     * Resolve URL encurtada para URL real
     */
    private function resolveShortUrl($shortUrl)
    {
        try {
            $response = $this->client->get($shortUrl, [
                'allow_redirects' => false, // Não seguir redirecionamentos automaticamente
            ]);

            // Verifica se tem cabeçalho de localização
            if ($response->hasHeader('Location')) {
                $location = $response->getHeader('Location')[0];

                return $location;
            }

            // Se não tiver header Location, tenta encontrar no corpo
            $body = (string) $response->getBody();
            if (preg_match('/href=["\']([^"\']+)["\']/', $body, $matches)) {
                $location = $matches[1];

                return $location;
            }
        } catch (Exception $e) {
            Log::warning('Erro ao resolver URL encurtada', ['url' => $shortUrl, 'error' => $e->getMessage()]);
        }

        return null;
    }

    public function supports(string $qrData): bool
    {
        $host = strtolower(parse_url($qrData, PHP_URL_HOST) ?? '');
        if ($host === 'nfce.sefaz.pe.gov.br') {
            return true;
        }

        $accessKey = $this->accessKey($qrData);

        return str_starts_with($accessKey, '26') && substr($accessKey, 20, 2) === '65';
    }

    private function getRealNFCeUrl(string $qrData): ?string
    {
        if (filter_var($qrData, FILTER_VALIDATE_URL)) {
            $host = strtolower(parse_url($qrData, PHP_URL_HOST) ?? '');
            if ($host === 'nfce.sefaz.pe.gov.br') {
                return $qrData;
            }

            $resolvedUrl = $this->resolveShortUrl($qrData);
            if ($resolvedUrl && $this->supports($resolvedUrl)) {
                return $resolvedUrl;
            }

            return null;
        }

        if (preg_match('/^\d{44}$/', $qrData)) {
            return $this->resolveQRCodeFromAccessKey($qrData);
        }

        return self::BASE_URL.'?p='.rawurlencode($qrData);
    }

    private function resolveQRCodeFromAccessKey(string $accessKey): ?string
    {
        $cookies = new CookieJar;

        $this->client->get(self::DFE_PORTAL_URL, [
            'cookies' => $cookies,
        ]);

        $response = $this->client->post(self::DFE_PORTAL_URL, [
            'cookies' => $cookies,
            'headers' => [
                'Referer' => self::DFE_PORTAL_URL,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                'sistema' => 'Dfe',
                'EhConsultaPublicaSiteSefaz' => 'True',
                'Ambiente' => '1',
                'ChaveAcessoDfe' => $accessKey,
            ],
        ]);

        return $this->extractQRCodeUrlFromDfeHtml((string) $response->getBody(), $accessKey);
    }

    private function extractQRCodeUrlFromDfeHtml(string $html, string $accessKey): ?string
    {
        if (! preg_match(
            '/<label>\s*QR-Code\s*<\/label>\s*<span[^>]*>\s*(https?:\/\/[^<\s]+)\s*<\/span>/iu',
            $html,
            $matches,
        )) {
            return null;
        }

        $url = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        if ($host !== 'nfce.sefaz.pe.gov.br' || ! str_contains($url, $accessKey.'|')) {
            return null;
        }

        return preg_replace('/^http:/i', 'https:', $url);
    }

    public function scrapeFromQRCode(string $qrData): array
    {
        try {
            $url = $this->getRealNFCeUrl($qrData);

            if (! $url) {
                return [
                    'status' => 'error',
                    'error' => 'Não foi possível obter URL válida',
                    'qr_data' => $qrData,
                ];
            }

            $response = $this->client->get($url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'application/xml,text/xml,text/html,application/xhtml+xml,*/*;q=0.8',
                ],
                'timeout' => 30,
                'verify' => false,
            ]);

            $content = $response->getBody()->getContents();

            // Verificar se é XML (mais preciso)
            $isXML = $this->isXMLContent($content);

            if ($isXML) {

                $result = $this->xmlParser->parseXML($content);

                if ($result['status'] === 'success') {
                    return [
                        'status' => 'success',
                        'message' => 'NFCe processada com sucesso (via XML)',
                        'url_consulta' => $url,
                        'tipo' => 'xml',
                        'data' => $result['data'],
                        'produtos_count' => count($result['data']['produtos']),
                        'valor_total' => $result['data']['nota']['valor_total'] ?? 0,
                    ];
                } else {
                    return $result;
                }
            } else {
                return $this->parseHTML($content, $url);
            }
        } catch (Exception $e) {
            Log::error('Erro ao scrapear NFCe:', [
                'error' => $e->getMessage(),
                'qr_data' => $qrData,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'qr_data' => $qrData,
            ];
        }
    }

    /**
     * Detecta se o conteúdo é XML
     */
    private function isXMLContent($content)
    {
        // Remove espaços no início
        $content = ltrim($content);

        // Verifica marcadores comuns de XML
        $xmlIndicators = [
            '<?xml' => 0,
            '<nfeProc' => 0,
            '<NFe' => 0,
            '<infNFe' => 0,
        ];

        foreach ($xmlIndicators as $indicator => $position) {
            if (stripos($content, $indicator) === 0 || stripos($content, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    public function parseHTML(string $html, string $url = ''): array
    {
        try {

            $crawler = new Crawler($html);

            // Verificar se a página tem conteúdo válido
            $pageText = $crawler->text();
            if (strlen($pageText) < 100) {
                throw new Exception('Página HTML muito curta ou vazia');
            }

            // Extrair dados usando os métodos corrigidos
            $dados = [
                'emitente' => $this->extrairEmitente($crawler),
                'destinatario' => $this->extrairDestinatario($crawler),
                'nota' => $this->extrairDadosNota($crawler),
                'produtos' => $this->extrairProdutos($crawler),
                'pagamento' => $this->extrairPagamento($crawler),
                'total' => $this->extrairTotal($crawler),
                'informacoes_adicionais' => $this->extrairInformacoesComplementares($crawler),
            ];

            $dados['dados_nota'] = $dados['nota'];
            $dados['protocolo'] = $this->extrairProtocolo($crawler, $dados['nota']);
            $dados['chave_acesso'] = $dados['nota']['chave_acesso'];

            return [
                'status' => 'success',
                'message' => 'NFCe processada com sucesso (via HTML)',
                'url_consulta' => $url,
                'tipo' => 'html',
                'data' => $dados,
                'produtos_count' => count($dados['produtos']),
                'valor_total' => $dados['total']['valor_total'] ?? 0,
            ];
        } catch (Exception $e) {

            throw $e;
        }
    }

    private function accessKey(string $qrData): string
    {
        if (filter_var($qrData, FILTER_VALIDATE_URL)) {
            parse_str(parse_url($qrData, PHP_URL_QUERY) ?? '', $query);
            $qrData = $query['p'] ?? '';
        }

        return preg_replace('/\D/', '', explode('|', $qrData)[0]) ?? '';
    }

    private function extrairEmitente(Crawler $crawler)
    {
        $emitente = [
            'razao_social' => '',
            'cnpj' => '',
            'endereco' => '',
            'bairro' => '',
            'municipio' => '',
            'uf' => '',
            'cep' => '',
            'telefone' => '',
            'ie' => '',
        ];

        try {
            // Método 1: Procurar por div com classe específica
            $selectors = [
                'div.txtCenter',
                'div.text-center',
                'div.emitente',
                'div:contains("CNPJ")',
                'div:contains("IE:")',
            ];

            foreach ($selectors as $selector) {
                if ($crawler->filter($selector)->count() > 0) {
                    $text = $crawler->filter($selector)->text();
                    $emitente = $this->parseEmitenteFromText($text, $emitente);
                    if (! empty($emitente['razao_social']) && ! empty($emitente['cnpj'])) {
                        return $emitente;
                    }
                }
            }

            // Método 2: Procurar em toda a página
            $pageText = $crawler->text();
            $emitente = $this->parseEmitenteFromText($pageText, $emitente);
        } catch (Exception $e) {
            Log::warning('Erro ao extrair emitente', ['error' => $e->getMessage()]);
        }

        return $emitente;
    }

    private function parseEmitenteFromText($text, $emitente)
    {
        // Extrair CNPJ
        if (preg_match('/CNPJ:\s*([\d.\/\-]+)/i', $text, $matches)) {
            $emitente['cnpj'] = trim($matches[1]);
        }

        // Extrair IE
        if (preg_match('/IE:\s*([\d.\/\-]+)/i', $text, $matches)) {
            $emitente['ie'] = trim($matches[1]);
        }

        // Extrair endereço completo
        $lines = explode("\n", $text);
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Razão social geralmente é a primeira linha significativa
            if (
                empty($emitente['razao_social']) &&
                ! str_contains($line, 'CNPJ') &&
                ! str_contains($line, 'IE') &&
                ! str_contains($line, 'End:') &&
                ! str_contains($line, 'Fone:') &&
                ! str_contains($line, 'CEP:') &&
                strlen($line) > 5
            ) {
                $emitente['razao_social'] = $line;
            }

            // Endereço
            if (str_contains($line, 'End:')) {
                $emitente['endereco'] = str_replace('End:', '', $line);
            }

            // Bairro/Município/UF
            if (preg_match('/(.+)\s+-\s+(.+)\/([A-Z]{2})/', $line, $matches)) {
                $emitente['bairro'] = trim($matches[1]);
                $emitente['municipio'] = trim($matches[2]);
                $emitente['uf'] = trim($matches[3]);
            }

            // CEP
            if (preg_match('/CEP:\s*(\d{5}-\d{3})/i', $line, $matches)) {
                $emitente['cep'] = $matches[1];
            }

            // Telefone
            if (preg_match('/Fone:\s*(.+)/i', $line, $matches)) {
                $emitente['telefone'] = trim($matches[1]);
            }
        }

        return $emitente;
    }

    private function extrairDestinatario(Crawler $crawler)
    {
        $destinatario = [
            'nome' => 'CONSUMIDOR FINAL',
            'cpf_cnpj' => '',
            'endereco' => '',
        ];

        try {
            $pageText = $crawler->text();

            // Procurar CPF (###.###.###-##)
            if (preg_match('/(\d{3}\.\d{3}\.\d{3}-\d{2})/', $pageText, $matches)) {
                $destinatario['cpf_cnpj'] = $matches[1];
            }
            // Procurar CNPJ (##.###.###/####-##)
            elseif (preg_match('/(\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})/', $pageText, $matches)) {
                $destinatario['cpf_cnpj'] = $matches[1];
            }
        } catch (Exception $e) {
            Log::warning('Erro ao extrair destinatário', ['error' => $e->getMessage()]);
        }

        return $destinatario;
    }

    private function extrairDadosNota(Crawler $crawler)
    {
        $nota = [
            'numero' => '',
            'serie' => '',
            'chave_acesso' => '',
            'data_emissao' => '',
            'data_entrada_saida' => '',
            'modelo' => 'NFC-e',
            'natureza_operacao' => '',
            'protocolo' => '',
            'valor_total' => 0,
            'valor_desconto' => 0,
            'valor_frete' => 0,
            'valor_seguro' => 0,
            'valor_outras_despesas' => 0,
        ];

        try {
            $pageText = $crawler->text();

            // Extrair número da nota
            if (preg_match('/Nº[:\s]*([\d\-\.\/]+)/i', $pageText, $matches)) {
                $nota['numero'] = trim($matches[1]);
            } elseif (preg_match('/Número[:\s]*([\d\-\.\/]+)/i', $pageText, $matches)) {
                $nota['numero'] = trim($matches[1]);
            }

            // Extrair série
            if (preg_match('/Série[:\s]*([\d\-\.\/]+)/i', $pageText, $matches)) {
                $nota['serie'] = trim($matches[1]);
            }

            // Extrair data de emissão
            if (preg_match('/(?:Data de Emissão|Emissão)[:\s]*([\d]{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})/iu', $pageText, $matches)) {
                $nota['data_emissao'] = trim($matches[1]);
            }

            // Extrair valor total
            if (preg_match('/Valor Total[:\s]*R?\$\s*([\d,\.]+)/i', $pageText, $matches)) {
                $nota['valor_total'] = $this->parseValorMonetario($matches[1]);
            }

            // Tentar extrair chave de acesso
            if ($crawler->filter('span.chave')->count() > 0) {
                $nota['chave_acesso'] = preg_replace('/\D/', '', $crawler->filter('span.chave')->first()->text());
            } elseif (preg_match('/(?:\d\s*){44}/', $pageText, $matches)) {
                $nota['chave_acesso'] = preg_replace('/\D/', '', $matches[0]);
            }
        } catch (Exception $e) {
            Log::warning('Erro ao extrair dados da nota', ['error' => $e->getMessage()]);
        }

        return $nota;
    }

    private function extrairProdutos(Crawler $crawler)
    {
        $produtos = [];

        try {
            // Tenta encontrar a tabela de produtos
            $table = $this->findProductsTable($crawler);

            if ($table->count() > 0) {
                $this->extractProductsFromTable($table, $produtos);
            } else {
                // Se não encontrou tabela específica, tenta extrair de qualquer tabela
                $this->extractProductsFromAllTables($crawler, $produtos);
            }
        } catch (Exception $e) {
            Log::warning('Erro ao extrair produtos', ['error' => $e->getMessage()]);
        }

        return $produtos;
    }

    private function findProductsTable(Crawler $crawler)
    {
        // Tenta seletores específicos para tabela de produtos
        $selectors = [
            'table[class*="prod"]',
            'table[id*="prod"]',
            'table:contains("Código")',
            'table:contains("Descrição")',
            'table:contains("Qtde")',
            'table:contains("UN")',
            'table:contains("Vl Unit")',
            'table:contains("Vl Total")',
        ];

        foreach ($selectors as $selector) {
            $table = $crawler->filter($selector)->first();
            if ($table->count() > 0) {
                return $table;
            }
        }

        return new Crawler;
    }

    private function extractProductsFromTable(Crawler $table, array &$produtos)
    {
        $table->filter('tr')->each(function (Crawler $row) use (&$produtos) {
            $cells = $row->filter('td');

            // Precisa ter pelo menos 3 células para ser um produto
            if ($cells->count() >= 3) {
                $rowText = $row->text();

                // Ignora linhas que são cabeçalho ou totais
                $ignoreKeywords = ['código', 'descrição', 'quantidade', 'unidade', 'total', 'subtotal'];
                $isHeader = false;

                foreach ($ignoreKeywords as $keyword) {
                    if (stripos($rowText, $keyword) !== false) {
                        $isHeader = true;
                        break;
                    }
                }

                if (! $isHeader) {
                    $produto = $this->extractProductFromRow($row);
                    if ($produto && (! empty($produto['descricao']) || ! empty($produto['codigo']))) {
                        $produtos[] = $produto;
                    }
                }
            }
        });
    }

    private function extractProductFromRow(Crawler $row)
    {
        $cells = $row->filter('td');
        $cellCount = $cells->count();

        $produto = [
            'codigo' => '',
            'descricao' => '',
            'quantidade' => 0,
            'unidade' => '',
            'valor_unitario' => 0,
            'valor_total' => 0,
        ];

        // Padrão mais comum: código, descrição, quantidade, unidade, vl unit, vl total
        if ($cellCount >= 6) {
            $produto['codigo'] = $this->limparTexto($cells->eq(0)->text());
            $produto['descricao'] = $this->limparTexto($cells->eq(1)->text());
            $produto['quantidade'] = $this->parseFloat($cells->eq(2)->text());
            $produto['unidade'] = $this->limparTexto($cells->eq(3)->text());
            $produto['valor_unitario'] = $this->parseValorMonetario($cells->eq(4)->text());
            $produto['valor_total'] = $this->parseValorMonetario($cells->eq(5)->text());
        }
        // Padrão alternativo: descrição, quantidade, unidade, valor
        elseif ($cellCount >= 4) {
            $produto['descricao'] = $this->limparTexto($cells->eq(0)->text());
            $produto['quantidade'] = $this->parseFloat($cells->eq(1)->text());
            $produto['unidade'] = $this->limparTexto($cells->eq(2)->text());

            // Última célula pode ser valor total
            $lastCellText = $cells->eq($cellCount - 1)->text();
            if (str_contains($lastCellText, 'R$')) {
                $produto['valor_total'] = $this->parseValorMonetario($lastCellText);
                if ($produto['quantidade'] > 0) {
                    $produto['valor_unitario'] = $produto['valor_total'] / $produto['quantidade'];
                }
            }
        }

        return $produto;
    }

    private function extractProductsFromAllTables(Crawler $crawler, array &$produtos)
    {
        $crawler->filter('table')->each(function (Crawler $table) use (&$produtos) {
            // Verifica se esta tabela pode conter produtos
            $tableText = $table->text();
            $hasProductIndicators =
                stripos($tableText, 'un') !== false ||
                stripos($tableText, 'kg') !== false ||
                stripos($tableText, 'R$') !== false;

            if ($hasProductIndicators) {
                $this->extractProductsFromTable($table, $produtos);
            }
        });
    }

    private function extrairPagamento(Crawler $crawler)
    {
        $pagamentos = [];

        try {
            // Procurar em toda a página por padrões de pagamento
            $pageText = $crawler->text();

            // Procura por padrões como: "Dinheiro R$ 50,00" ou "Cartão de Crédito R$ 100,00"
            $patterns = [
                '/(Dinheiro|Cartão\s+(?:de\s+)?(?:Crédito|Débito)|PIX|Vale|Cheque|Outros)[\s:]+R?\$\s*([\d.,]+)/i',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $pageText, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $forma = $this->limparTexto($match[1]);
                        $valor = $this->parseValorMonetario($match[2]);

                        if ($valor > 0.01 && ! empty($forma)) {
                            $pagamentos[] = [
                                'forma' => $forma,
                                'valor' => $valor,
                            ];
                        }
                    }
                }
            }

            // Se não encontrou, procurar em tabelas
            if (empty($pagamentos)) {
                $crawler->filter('table')->each(function (Crawler $table) use (&$pagamentos) {
                    $rows = $table->filter('tr');
                    $rows->each(function (Crawler $row) use (&$pagamentos) {
                        $cells = $row->filter('td');
                        if ($cells->count() === 2) {
                            $text1 = $this->limparTexto($cells->eq(0)->text());
                            $text2 = $this->limparTexto($cells->eq(1)->text());

                            // Verifica se parece ser um pagamento
                            $isPayment = (
                                (stripos($text2, 'R$') !== false || is_numeric(str_replace(',', '.', $text2))) &&
                                ! stripos($text1, 'total') &&
                                ! stripos($text1, 'troco') &&
                                ! stripos($text1, 'subtotal')
                            );

                            if ($isPayment) {
                                $pagamentos[] = [
                                    'forma' => $text1,
                                    'valor' => $this->parseValorMonetario($text2),
                                ];
                            }
                        }
                    });
                });
            }
        } catch (Exception $e) {
            Log::warning('Erro ao extrair pagamento', ['error' => $e->getMessage()]);
        }

        return $pagamentos;
    }

    private function extrairTotal(Crawler $crawler)
    {
        $total = [
            'valor_produtos' => 0,
            'valor_desconto' => 0,
            'valor_frete' => 0,
            'valor_total' => 0,
        ];

        try {
            $pageText = $crawler->text();

            // Extrair valor total
            if (preg_match('/Valor Total[:\s]*R?\$\s*([\d,\.]+)/i', $pageText, $matches)) {
                $total['valor_total'] = $this->parseValorMonetario($matches[1]);
            }

            // Extrair desconto
            if (preg_match('/Desconto[:\s]*R?\$\s*([\d,\.]+)/i', $pageText, $matches)) {
                $total['valor_desconto'] = $this->parseValorMonetario($matches[1]);
            }
        } catch (Exception $e) {
            Log::warning('Erro ao extrair total', ['error' => $e->getMessage()]);
        }

        return $total;
    }

    private function extrairProtocolo(Crawler $crawler, array $nota): array
    {
        $protocolo = [
            'numero' => '',
            'data_recebimento' => $nota['data_emissao'] ?? '',
            'status' => '',
            'motivo' => '',
        ];

        $text = $crawler->text();
        if (preg_match('/Protocolo de Autorização:\s*(\d+)\s+(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})/iu', $text, $matches)) {
            $protocolo['numero'] = $matches[1];
            $protocolo['data_recebimento'] = $matches[2];
            $protocolo['status'] = 'Autorizada';
        }

        return $protocolo;
    }

    private function extrairInformacoesComplementares(Crawler $crawler)
    {
        $info = [
            'observacoes' => '',
            'informacao_fisco' => '',
            'informacao_contribuinte' => '',
        ];

        try {
            $pageText = $crawler->text();

            // Procura por padrões de informações complementares
            $patterns = [
                'observacoes' => '/Observações[:\s]*\n*(.+?)(?=\n{2}|$)/is',
                'fisco' => '/Informação\s+(?:ao\s+)?Fisco[:\s]*\n*(.+?)(?=\n{2}|$)/i',
                'contribuinte' => '/Informação\s+(?:ao\s+)?Contribuinte[:\s]*\n*(.+?)(?=\n{2}|$)/i',
            ];

            foreach ($patterns as $key => $pattern) {
                if (preg_match($pattern, $pageText, $matches)) {
                    $info[$key === 'observacoes' ? 'observacoes' : ($key === 'fisco' ? 'informacao_fisco' : 'informacao_contribuinte')]
                        = $this->limparTexto($matches[1]);
                }
            }
        } catch (Exception $e) {
            Log::warning('Erro ao extrair info complementares', ['error' => $e->getMessage()]);
        }

        return $info;
    }

    // ============ MÉTODOS AUXILIARES ============

    private function limparTexto($text)
    {
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function parseValorMonetario($text)
    {
        $text = $this->limparTexto($text);

        // Remove tudo que não é número, ponto, vírgula
        $text = preg_replace('/[^\d,\.]/', '', $text);

        // Converte formato brasileiro para float
        if (strpos($text, ',') !== false && strpos($text, '.') !== false) {
            // Tem ambos: 1.234,56 -> remove ponto de milhar, converte vírgula decimal
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        } elseif (strpos($text, ',') !== false) {
            // Só tem vírgula: 1234,56
            if (substr_count($text, ',') === 1) {
                $text = str_replace(',', '.', $text);
            }
        }

        return floatval($text);
    }

    private function parseFloat($text)
    {
        $text = $this->limparTexto($text);
        $text = str_replace(',', '.', $text);

        return floatval($text);
    }
}
