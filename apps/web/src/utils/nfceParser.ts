// utils/nfce-parser.ts

export interface NFCeData {
    url: string;
    estado: string;
    chaveAcesso: string;
    parametros: {
        p: string;
        versao: string;
        ambiente: string;
        operacao: string;
        token: string;
    };
    dadosEmitente?: {
        cnpj: string;
        razaoSocial?: string;
        endereco?: string;
    };
    dadosNota?: {
        numero: string;
        serie: string;
        modelo: string;
        dataEmissao?: Date;
        valorTotal?: number;
    };
    rawData: string;
}

/**
 * Extrai e estrutura os dados de uma URL de NFCe
 */
export function parseNFCeURL(input: string): NFCeData | null {
    try {
        let rawParam: string;
        let originalURL: string = input;

        // Caso 1: É uma URL completa
        if (input.includes('?p=')) {
            try {
                const urlObj = new URL(input);
                const pParam = urlObj.searchParams.get('p');
                if (!pParam) {
                    return null;
                }
                rawParam = pParam;
            } catch {
                // Se falhar ao criar URL, tenta extrair manualmente
                const match = input.match(/[?&]p=([^&]+)/);
                rawParam = match ? match[1] : input;
            }
        }
        // Caso 2: É apenas o parâmetro p
        else if (input.includes('|')) {
            rawParam = input;
            // Reconstruir uma URL completa para referência
            const chave = input.split('|')[0];
            originalURL = `https://nfce.sefaz.pe.gov.br/nfce/consulta?p=${input}`;
        }
        // Caso 3: Formato inválido
        else {
            return null;
        }

        // Separar os parâmetros
        const pParts = rawParam.split('|');

        if (pParts.length < 5) {
            console.warn('Parâmetro p incompleto:', rawParam);
            return null;
        }

        const chaveAcesso = pParts[0];

        // Validar chave de acesso (44 caracteres para NFC-e)
        if (chaveAcesso.length !== 44) {
            console.warn('Chave de acesso inválida:', chaveAcesso);
            return null;
        }

        // Extrair informações da chave de acesso
        const estadoCode = chaveAcesso.substring(0, 2);
        const anoMes = chaveAcesso.substring(2, 6);
        const cnpj = chaveAcesso.substring(6, 20);
        const modelo = chaveAcesso.substring(20, 22);
        const serie = chaveAcesso.substring(22, 25);
        const numero = chaveAcesso.substring(25, 34);

        // Mapeamento de códigos de estado
        const estados: { [key: string]: string } = {
            '11': 'RO', '12': 'AC', '13': 'AM', '14': 'RR', '15': 'PA',
            '16': 'AP', '17': 'TO', '21': 'MA', '22': 'PI', '23': 'CE',
            '24': 'RN', '25': 'PB', '26': 'PE', '27': 'AL', '28': 'SE',
            '29': 'BA', '31': 'MG', '32': 'ES', '33': 'RJ', '35': 'SP',
            '41': 'PR', '42': 'SC', '43': 'RS', '50': 'MS', '51': 'MT',
            '52': 'GO', '53': 'DF'
        };

        // Mapeamento de modelos
        const modelos: { [key: string]: string } = {
            '55': 'NF-e',
            '57': 'CT-e',
            '65': 'NFC-e'
        };

        return {
            url: originalURL,
            estado: estados[estadoCode] || estadoCode,
            chaveAcesso,
            parametros: {
                p: rawParam,
                versao: pParts[1],
                ambiente: pParts[2], // 1=Produção, 2=Homologação
                operacao: pParts[3],
                token: pParts[4]
            },
            dadosEmitente: {
                cnpj: formatCNPJ(cnpj)
            },
            dadosNota: {
                numero,
                serie,
                modelo: modelos[modelo] || modelo,
                dataEmissao: parseDataFromChave(anoMes)
            },
            rawData: rawParam
        };
    } catch (error) {
        console.error('Erro ao parsear NFCe:', error, 'Input:', input);
        return null;
    }
}

export function isValidNFCeInput(input: string): boolean {
    try {
        // Verifica se tem o padrão básico
        const hasChave = /^\d{44}/.test(input) || /\d{44}\|/.test(input);
        const hasParams = input.includes('|');
        return hasChave && hasParams;
    } catch {
        return false;
    }
}

/**
 * Formata CNPJ
 */
function formatCNPJ(cnpj: string): string {
    return cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
}

/**
 * Parseia data a partir do ano/mês na chave
 */
function parseDataFromChave(anoMes: string): Date | undefined {
    try {
        const ano = parseInt(anoMes.substring(0, 2));
        const mes = parseInt(anoMes.substring(2, 4)) - 1; // Meses são 0-indexed
        const anoCompleto = 2000 + ano;
        return new Date(anoCompleto, mes, 1);
    } catch {
        return undefined;
    }
}

/**
 * Valida se é uma URL de NFCe válida
 */
export function isValidNFCeURL(url: string): boolean {
    try {
        const parsed = parseNFCeURL(url);
        return parsed !== null;
    } catch {
        return false;
    }
}

/**
 * Gera a URL de consulta da SEFAZ
 */
export function generateConsultaURL(chaveAcesso: string, token: string): string {
    const estado = chaveAcesso.substring(0, 2);

    // URLs por estado (exemplos)
    const endpoints: { [key: string]: string } = {
        '26': 'https://nfce.sefaz.pe.gov.br/nfce/consulta',
        '35': 'https://www.nfce.fazenda.sp.gov.br/consulta',
        '31': 'https://nfce.fazenda.mg.gov.br/portalnfce',
        '53': 'https://www.fazenda.df.gov.br/nfce',
        // Adicione outros estados conforme necessário
    };

    const endpoint = endpoints[estado] || 'https://nfce.sefaz.pe.gov.br/nfce/consulta';
    return `${endpoint}?p=${chaveAcesso}|2|1|1|${token}`;
}