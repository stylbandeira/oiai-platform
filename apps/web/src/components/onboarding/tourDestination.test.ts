import { describe, expect, it } from "vitest";
import { getListTourDestination, moveTourStep } from "./tourDestination";

describe("destino do passeio de listas", () => {
  it("leva quem ainda não tem listas ao fluxo de criação", () => {
    expect(getListTourDestination([])).toEqual({
      path: "/new-list",
      state: { showOptimizeTourAfterCreate: true },
    });
  });

  it("leva a uma lista ativa para explicar a otimização", () => {
    expect(getListTourDestination([
      { id: 2, status: "completed" },
      { id: 7, status: "active" },
    ])).toEqual({ path: "/list/7", state: { showOptimizeTour: true } });
  });

  it("não tenta otimizar uma lista concluída", () => {
    expect(getListTourDestination([{ id: 2, status: "completed" }]).path).toBe("/new-list");
  });
});

describe("navegação do passeio", () => {
  it("avança e volta entre as etapas sem ultrapassar os limites", () => {
    expect(moveTourStep(0, 1, 4)).toBe(1);
    expect(moveTourStep(2, -1, 4)).toBe(1);
    expect(moveTourStep(0, -1, 4)).toBe(0);
    expect(moveTourStep(3, 1, 4)).toBe(3);
  });
});
