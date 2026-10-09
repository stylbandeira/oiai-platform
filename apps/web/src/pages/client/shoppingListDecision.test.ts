import { describe, expect, it } from "vitest";
import { applyDecisionToList, toCreateListItems } from "./shoppingListDecision";
import type { SelectedListItem } from "./shoppingListDecision";

const soap: SelectedListItem = {
  product: {
    id: 1,
    name: "Sabonete Lavanda Johnsons",
    average_price: 2.79,
    category: "Higiene",
    isFavorite: false,
    unit: "un",
    unity_quantity: 1,
    unity: "un",
    unity_id: 1,
    normalization_validated: false,
    normalization_next_attribute: "quantity",
  },
  quantity: 1,
  unity: "un",
};

const sugar: SelectedListItem = {
  ...soap,
  product: { ...soap.product, id: 2, name: "Açúcar", unity_quantity: 1, unity: "kg" },
  quantity: 2,
  unity: "kg",
};

describe("decisão de normalização na criação de lista", () => {
  it("mantém uma embalagem ao validar que cada sabonete contém 80g", () => {
    const items = applyDecisionToList([soap, sugar], {
      id: 1,
      name: soap.product.name,
      quantity: 80,
      unity_quantity: 80,
      unity: "g",
      unity_id: 3,
      normalization_validated: true,
      normalization_next_attribute: null,
    });

    expect(items[0].quantity).toBe(1);
    expect(items[0].product.unity_quantity).toBe(80);
    expect(items[0].product.unity).toBe("g");
    expect(items[0].product.normalization_next_attribute).toBeNull();
    expect(items[0].quantity * items[0].product.unity_quantity).toBe(80);
    expect(items[0].quantity * items[0].product.average_price).toBe(2.79);
    expect(items[1]).toBe(sugar);
    expect(toCreateListItems(items)).toEqual([
      { product: { id: 1 }, quantity: 1 },
      { product: { id: 2 }, quantity: 2 },
    ]);
  });

  it("mantém a quantidade escolhida mesmo se a API retornar apenas quantity", () => {
    const items = applyDecisionToList([{ ...soap, quantity: 3 }], {
      id: 1,
      name: soap.product.name,
      quantity: 80,
      unity: "g",
    });

    expect(items[0].quantity).toBe(3);
    expect(items[0].product.unity_quantity).toBe(80);
    expect(toCreateListItems(items)[0].quantity).toBe(3);
  });

  it("validar apenas o nome não altera nem a quantidade de embalagens nem seu conteúdo", () => {
    const items = applyDecisionToList([soap], {
      id: 1,
      name: "Sabonete Lavanda",
      name_normalization_validated: true,
      normalization_next_attribute: "quantity",
    });

    expect(items[0].quantity).toBe(1);
    expect(items[0].product.unity_quantity).toBe(1);
    expect(items[0].product.name).toBe("Sabonete Lavanda");
    expect(items[0].product.normalization_next_attribute).toBe("quantity");
  });
});
