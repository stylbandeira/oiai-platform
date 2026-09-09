<?php

namespace Tests\Unit\Services;

use App\Services\NFCe\SaoPauloNFCeProvider;
use App\Services\NFCe\SantaCatarinaNFCeProvider;
use App\Services\NFCe\PernambucoNFCeProvider;
use App\Services\NFCeScraperService;
use App\Services\NFCeXMLParserService;
use App\Contracts\NFCe\StateNFCeProvider;
use Illuminate\Support\Facades\Cache;
use ReflectionMethod;
use Tests\TestCase;

class NFCeScraperServiceTest extends TestCase
{
    public function test_normalizes_qr_data_and_caches_repeated_lookup(): void
    {
        Cache::fake();
        $calls = 0;
        $provider = new class($calls) implements StateNFCeProvider {
            private $calls;
            public function __construct(int &$calls) { $this->calls =& $calls; }
            public function supports(string $qrData): bool { return $qrData === 'qr-data'; }
            public function scrapeFromQRCode(string $qrData): array
            {
                $this->calls++;
                return ['status' => 'success', 'data' => ['chave_acesso' => 'key']];
            }
        };
        $service = new NFCeScraperService([$provider]);

        $first = $service->scrapeFromQRCode(" qr-\ndata ");
        $second = $service->scrapeFromQRCode('qr-data');

        $this->assertSame('success', $first['status']);
        $this->assertSame($first, $second);
        $this->assertSame(1, $calls);
    }

    public function test_returns_error_when_no_state_provider_supports_input(): void
    {
        $service = new NFCeScraperService([]);

        $result = $service->scrapeFromQRCode('99999999999999999999999999999999999999999999');

        $this->assertSame('error', $result['status']);
        $this->assertSame('Não existe provider cadastrado para a UF informada.', $result['error']);
    }

    public function test_extracts_pernambuco_qr_code_url_from_dfe_access_key_response(): void
    {
        $accessKey = '26260724333585000120650100003083781137031084';
        $html = '<label>QR-Code</label><span style="word-break: break-all">'
            .'http://nfce.sefaz.pe.gov.br/nfce-web/consultarNFCe?p='
            .$accessKey.'|2|1|1|5DAD78B79B33A16C8A1E567BCD4B1ED0F994C193'
            .'</span>';
        $provider = new PernambucoNFCeProvider(new NFCeXMLParserService());
        $extract = new ReflectionMethod($provider, 'extractQRCodeUrlFromDfeHtml');

        $url = $extract->invoke($provider, $html, $accessKey);

        $this->assertSame(
            'https://nfce.sefaz.pe.gov.br/nfce-web/consultarNFCe?p='
            .$accessKey.'|2|1|1|5DAD78B79B33A16C8A1E567BCD4B1ED0F994C193',
            $url,
        );
    }

    public function test_sao_paulo_provider_owns_detection_and_url_creation(): void
    {
        $accessKey = '35260747508411094703651070005749261615098569';
        $provider = new SaoPauloNFCeProvider();

        $this->assertTrue($provider->supports($accessKey));
        $this->assertFalse($provider->supports('26260747508411094703651070005749261615098569'));
        $this->assertSame(
            'https://www.nfce.fazenda.sp.gov.br/NFCeConsultaPublica/Paginas/ConsultaQRCode.aspx?p='.$accessKey.'%7C3%7C1',
            $provider->buildUrl($accessKey),
        );
    }

    public function test_parses_sao_paulo_public_consultation_html(): void
    {
        $html = <<<'HTML'
        <div class="txtCenter">
          <div class="txtTopo">CIA BRASILEIRA DE DISTRIBUICAO</div>
          <div class="text">CNPJ: 47.508.411/0947-03</div>
          <div class="text">RUA PAMPLONA, 000816, BELA VISTA, SAO PAULO, SP</div>
        </div>
        <table id="tabResult"><tr><td>
          <span class="txtTit">PAO FOR PANCO 500G</span>
          <span class="RCod">(Código: 1161717)</span>
          <span class="Rqtd"><strong>Qtde.:</strong>1</span>
          <span class="RUN"><strong>UN:</strong>UN</span>
          <span class="RvlUnit"><strong>Vl. Unit.:</strong>11,49</span>
        </td><td><span class="valor">11,49</span></td></tr></table>
        <div id="totalNota">
          <div id="linhaTotal"><label>Valor total R$:</label><span class="totalNumb">11,49</span></div>
          <div id="linhaTotal"><label>Valor a pagar R$:</label><span class="totalNumb">11,49</span></div>
          <div id="linhaTotal"><label class="tx">Cartão de Crédito</label><span class="totalNumb">11,49</span></div>
        </div>
        <div id="infos">Número: 574926 Série: 107 Emissão: 20/07/2026 09:34:30
          Protocolo de Autorização: 135264899771704 20/07/2026 09:34:33
          <span class="chave">3526 0747 5084 1109 4703 6510 7000 5749 2616 1509 8569</span>
        </div>
        HTML;

        $provider = new SaoPauloNFCeProvider();
        $result = $provider->parseHtml($html, 'https://www.nfce.fazenda.sp.gov.br/');

        $this->assertSame('success', $result['status']);
        $this->assertSame('35260747508411094703651070005749261615098569', $result['data']['chave_acesso']);
        $this->assertSame('CIA BRASILEIRA DE DISTRIBUICAO', $result['data']['emitente']['razao_social']);
        $this->assertSame('PAO FOR PANCO 500G', $result['data']['produtos'][0]['descricao']);
        $this->assertSame(11.49, $result['data']['produtos'][0]['valor_unitario']);
        $this->assertSame('20/07/2026 09:34:33', $result['data']['protocolo']['data_recebimento']);
    }

    public function test_santa_catarina_provider_owns_detection_and_url_creation(): void
    {
        $accessKey = '42260752783575000121650010000785941922606426';
        $qrParameter = $accessKey.'|2|1|1|F664A7CEB56CF83F99E0684D74E8A0991C9098F4';
        $provider = new SantaCatarinaNFCeProvider();

        $this->assertTrue($provider->supports($accessKey));
        $this->assertTrue($provider->supports('https://sat.sef.sc.gov.br/nfce/consulta?p='.$qrParameter));
        $this->assertTrue($provider->supports('https://hom.sat.sef.sc.gov.br/nfce/consulta?p='.$qrParameter));
        $this->assertFalse($provider->supports('35260752783575000121650010000785941922606426'));
        $this->assertSame(
            'https://sat.sef.sc.gov.br/nfce/consulta?p='.rawurlencode($qrParameter),
            $provider->buildUrl($qrParameter),
        );
    }

    public function test_parses_santa_catarina_public_consultation_html(): void
    {
        $html = <<<'HTML'
        <div class="txtCenter">
          <div class="txtTopo">MERCADO CATARINENSE LTDA</div>
          <div class="text">CNPJ: 52.783.575/0001-21</div>
          <div class="text">RUA DAS FLORES, 100, CENTRO, FLORIANOPOLIS</div>
        </div>
        <table id="tabResult"><tr><td>
          <span class="txtTit">CAFE 500G</span>
          <span class="RCod">(Código: 123)</span>
          <span class="Rqtd"><strong>Qtde.:</strong>1</span>
          <span class="RUN"><strong>UN:</strong>UN</span>
          <span class="RvlUnit"><strong>Vl. Unit.:</strong>18,90</span>
        </td><td><span class="valor">18,90</span></td></tr></table>
        <div id="totalNota">
          <div id="linhaTotal"><label>Valor total R$:</label><span class="totalNumb">18,90</span></div>
          <div id="linhaTotal"><label>Valor a pagar R$:</label><span class="totalNumb">18,90</span></div>
        </div>
        <div id="infos">Número: 78594 Série: 1 Emissão: 20/07/2026 09:34:30
          <span class="chave">4226 0752 7835 7500 0121 6500 1000 0785 9419 2260 6426</span>
        </div>
        HTML;

        $provider = new SantaCatarinaNFCeProvider();
        $result = $provider->parseHtml($html, 'https://sat.sef.sc.gov.br/nfce/consulta');

        $this->assertSame('success', $result['status']);
        $this->assertSame('NFCe processada com sucesso (via HTML/SC)', $result['message']);
        $this->assertSame('SC', $result['data']['emitente']['uf']);
        $this->assertSame('42260752783575000121650010000785941922606426', $result['data']['chave_acesso']);
        $this->assertSame('CAFE 500G', $result['data']['produtos'][0]['descricao']);
        $this->assertSame(18.90, $result['valor_total']);
    }

    public function test_santa_catarina_provider_rejects_security_verification_page(): void
    {
        $provider = new SantaCatarinaNFCeProvider();

        $this->expectExceptionMessage('O portal da NFC-e de Santa Catarina solicitou validação de segurança.');

        $provider->parseHtml(
            '<html><body><h1>Validação de segurança</h1><p>Efetue a validação de segurança para continuar.</p></body></html>',
            'https://sat.sef.sc.gov.br/tax.NET/SecurityVerify.aspx',
        );
    }
}
