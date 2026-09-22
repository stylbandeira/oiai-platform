/** Linha aceita pelo ProductImportRequest do backend. */
export interface ProductImportRow {
  name: string;
  quantity: number;
  unity: string | number;
  category: string | number;
  sku: string;
  img?: File | string | null;
}

/** Linha completa usada na exportação/listagem administrativa. */
export interface ProductExportRow {
  id: number;
  name: string;
  sku: string | null;
  ean: string | null;
  img: string | null;
  average_price: number | null;
  validated: boolean;
  mentioned_quantity: number | null;
  mentioned_quantity_variant: string | null;
  unity: string | null;
  unity_id: number | null;
  unity_quantity: number | null;
  category: string | null;
  companies_count: number | null;
  brand?: string | null;
  description?: string | null;
  updated_at?: string;
}

export type CsvValue = string | number | boolean | null;
export type CsvRow = Record<string, CsvValue>;
