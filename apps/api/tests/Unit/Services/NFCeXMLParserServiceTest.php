<?php

namespace Tests\Unit\Services;

use App\Services\NFCeXMLParserService;
use Tests\TestCase;

class NFCeXMLParserServiceTest extends TestCase
{
    public function test_parses_pernambuco_nested_nfce_response(): void
    {
        $xml = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <?xml-stylesheet type="text/xsl" href="xsl/an/NFCe_2.xsl"?>
        <nfeProc xmlns="http://www.portalfiscal.inf.br/nfe">
          <erro></erro>
          <consulta>0</consulta>
          <proc>
            <nfeProc versao="4.00">
              <NFe>
                <infNFe Id="NFe26260745543915100640650740000596441990985548" versao="4.00">
                  <ide>
                    <natOp>VENDA</natOp>
                    <mod>65</mod>
                    <serie>74</serie>
                    <nNF>59644</nNF>
                    <dhEmi>2026-07-15T09:30:35-03:00</dhEmi>
                    <cMunFG>2611101</cMunFG>
                  </ide>
                  <emit>
                    <CNPJ>45543915100640</CNPJ>
                    <xNome>CARREFOUR COM. E IND. LTDA</xNome>
                    <enderEmit><UF>PE</UF></enderEmit>
                  </emit>
                  <det nItem="1">
                    <prod>
                      <cProd>1057677</cProd>
                      <cEAN>07896080900148</cEAN>
                      <xProd>FOSFORO PARANA</xProd>
                      <NCM>36050000</NCM>
                      <CFOP>5102</CFOP>
                      <uCom>un</uCom>
                      <qCom>2.0000</qCom>
                      <vUnCom>2.98</vUnCom>
                      <vProd>5.96</vProd>
                    </prod>
                  </det>
                  <total>
                    <ICMSTot>
                      <vProd>5.96</vProd><vDesc>0</vDesc><vFrete>0</vFrete>
                      <vNF>5.96</vNF><vTotTrib>1.77</vTotTrib>
                      <vBC>5.96</vBC><vICMS>1.22</vICMS>
                    </ICMSTot>
                  </total>
                  <pag><detPag><tPag>17</tPag><vPag>5.96</vPag></detPag></pag>
                </infNFe>
              </NFe>
              <protNFe>
                <infProt>
                  <dhRecbto>2026-07-15T09:35:00-03:00</dhRecbto>
                  <nProt>226260646517888</nProt>
                  <cStat>100</cStat>
                  <xMotivo>Autorizado o uso da NF-e</xMotivo>
                </infProt>
              </protNFe>
            </nfeProc>
          </proc>
          null
        </nfeProc>
        XML;

        $result = (new NFCeXMLParserService())->parseXML($xml);

        $this->assertSame('success', $result['status']);
        $this->assertSame(
            'NFe26260745543915100640650740000596441990985548',
            $result['data']['chave_acesso'],
        );
        $this->assertSame('CARREFOUR COM. E IND. LTDA', $result['data']['emitente']['razao_social']);
        $this->assertSame('FOSFORO PARANA', $result['data']['produtos'][0]['descricao']);
        $this->assertSame('226260646517888', $result['data']['protocolo']['numero']);
        $this->assertSame('15/07/2026 09:35:00', $result['data']['protocolo']['data_recebimento']);
    }
}
