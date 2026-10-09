export interface ProductDecisionResult {
  id: number;
  name: string;
  quantity?: number | null;
  unity_quantity?: number | null;
  unity?: string | null;
  unit_id?: number | null;
  unity_id?: number | null;
  normalization_validated?: boolean;
  normalization_next_attribute?: "name" | "quantity" | "quantity_dimension" | null;
  name_normalization_validated?: boolean;
  quantity_normalization_validated?: boolean;
}

export interface ListProduct {
  id: number;
  name: string;
  average_price: number;
  category: string;
  isFavorite: boolean;
  unit: string;
  unity_quantity: number;
  unity: string;
  unity_id: number;
  img?: string;
  normalization_validated?: boolean;
  normalization_next_attribute?: "name" | "quantity" | "quantity_dimension" | null;
  name_normalization_validated?: boolean;
  quantity_normalization_validated?: boolean;
}

export interface SelectedListItem {
  product: ListProduct;
  // Number of packages selected by the user, never the package's content.
  quantity: number;
  unity: string;
}

export function applyProductDecision<T extends ListProduct>(product: T, decision: ProductDecisionResult): T {
  return {
    ...product,
    name: decision.name,
    unity_quantity: decision.unity_quantity ?? decision.quantity ?? product.unity_quantity,
    unity: decision.unity ?? product.unity,
    unit: decision.unity ?? product.unit,
    unity_id: decision.unity_id ?? decision.unit_id ?? product.unity_id,
    normalization_validated: decision.normalization_validated ?? product.normalization_validated,
    normalization_next_attribute: decision.normalization_next_attribute !== undefined
      ? decision.normalization_next_attribute
      : product.normalization_next_attribute,
    name_normalization_validated: decision.name_normalization_validated ?? product.name_normalization_validated,
    quantity_normalization_validated: decision.quantity_normalization_validated ?? product.quantity_normalization_validated,
  };
}

export function applyDecisionToList(items: SelectedListItem[], decision: ProductDecisionResult): SelectedListItem[] {
  return items.map((item) => {
    if (item.product.id !== decision.id) return item;

    const product = applyProductDecision(item.product, decision);
    return { ...item, product, unity: product.unity };
  });
}

export function toCreateListItems(items: SelectedListItem[]) {
  return items.map(({ product, quantity }) => ({ product: { id: product.id }, quantity }));
}
