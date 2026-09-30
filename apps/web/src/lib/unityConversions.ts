import type { components } from "@/types/api.generated";

export type Unity = components["schemas"]["Unity"];

export function convertQuantity(quantity: number, from: Unity, to: Unity): number | null {
  if (!from.base_unity_id || from.base_unity_id !== to.base_unity_id ||
      from.dimension !== to.dimension || from.convertion_factor <= 0 ||
      to.convertion_factor <= 0) {
    return null;
  }

  return quantity * from.convertion_factor / to.convertion_factor;
}

export function readableTotalQuantity(
  quantity: number,
  source: Unity | undefined,
  unities: Unity[],
): { quantity: number; unit: string } | null {
  if (!source || !Number.isFinite(quantity) || quantity < 0) return null;

  const largerUnits = unities
    .filter((unit) => unit.convertion_factor > source.convertion_factor &&
      convertQuantity(1, source, unit) !== null)
    .sort((a, b) => b.convertion_factor - a.convertion_factor);

  for (const unit of largerUnits) {
    const converted = convertQuantity(quantity, source, unit);
    if (converted !== null && converted >= 1) {
      return { quantity: converted, unit: unit.abbreviation };
    }
  }

  return { quantity, unit: source.abbreviation };
}

export function readableUnitPrice(
  packagePrice: number,
  packageQuantity: number,
  source: Unity | undefined,
  unities: Unity[],
): { price: number; unit: string } | null {
  if (!source || !Number.isFinite(packagePrice) || !Number.isFinite(packageQuantity) || packageQuantity <= 0) {
    return null;
  }

  const originalPrice = packagePrice / packageQuantity;
  if (originalPrice >= 0.01 || originalPrice <= 0) {
    return { price: originalPrice, unit: source.abbreviation };
  }

  const largerUnits = unities
    .filter((unit) => unit.convertion_factor > source.convertion_factor &&
      convertQuantity(1, source, unit) !== null)
    .sort((a, b) => a.convertion_factor - b.convertion_factor);

  for (const unit of largerUnits) {
    const converted = convertQuantity(packageQuantity, source, unit);
    if (converted && packagePrice / converted >= 0.01) {
      return { price: packagePrice / converted, unit: unit.abbreviation };
    }
  }

  return { price: originalPrice, unit: source.abbreviation };
}
