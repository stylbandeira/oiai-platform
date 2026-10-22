// @vitest-environment jsdom
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { ProductTour } from "./ProductTour";

const steps = [
  { target: "#create-list", title: "Criar lista", description: "Comece por aqui." },
  { target: "#scan-invoice", title: "Escanear nota", description: "Use o QR Code." },
];

function renderTour() {
  const create = vi.fn();
  const scan = vi.fn();
  const view = render(
    <>
      <button id="create-list" onClick={create}>Criar nova lista</button>
      <button id="scan-invoice" onClick={scan}>Escanear nota fiscal</button>
      <ProductTour steps={steps} storageKey="test-tour" />
    </>
  );
  return { ...view, create, scan };
}

beforeEach(() => {
  localStorage.clear();
  HTMLElement.prototype.scrollIntoView = vi.fn();
  HTMLElement.prototype.getClientRects = vi.fn(() => [new DOMRect(0, 0, 120, 40)] as unknown as DOMRectList);
});
afterEach(() => cleanup());

describe("passeio interativo", () => {
  it("começa na primeira visita, avança pelo controle real e não executa sua ação", () => {
    const { create, scan } = renderTour();
    expect(screen.getByRole("heading", { name: "Criar lista" })).toBeTruthy();
    expect(document.activeElement).toBe(screen.getByRole("heading", { name: "Criar lista" }));
    fireEvent.click(screen.getByRole("button", { name: "Criar nova lista" }));
    expect(create).not.toHaveBeenCalled();
    expect(screen.getByRole("heading", { name: "Escanear nota" })).toBeTruthy();
    expect(document.activeElement).toBe(screen.getByRole("heading", { name: "Escanear nota" }));
    fireEvent.click(screen.getByRole("button", { name: "Escanear nota fiscal" }));
    expect(scan).not.toHaveBeenCalled();
    expect(localStorage.getItem("test-tour")).toBe("completed");
  });

  it("permite voltar, concluir e não reaparece após remontagem", () => {
    const view = renderTour();
    fireEvent.click(screen.getByRole("button", { name: "Avançar" }));
    fireEvent.click(screen.getByRole("button", { name: "Voltar" }));
    expect(screen.getByRole("heading", { name: "Criar lista" })).toBeTruthy();
    fireEvent.click(screen.getByRole("button", { name: "Avançar" }));
    fireEvent.click(screen.getByRole("button", { name: "Concluir" }));
    view.unmount();
    renderTour();
    expect(screen.queryByRole("heading", { name: "Criar lista" })).toBeNull();
  });

  it("permite pular e fechar por teclado", () => {
    const view = renderTour();
    fireEvent.keyDown(document, { key: "ArrowRight" });
    expect(screen.getByRole("heading", { name: "Escanear nota" })).toBeTruthy();
    fireEvent.keyDown(document, { key: "ArrowLeft" });
    expect(screen.getByRole("heading", { name: "Criar lista" })).toBeTruthy();
    fireEvent.keyDown(document, { key: "Escape" });
    expect(localStorage.getItem("test-tour")).toBe("completed");
    view.unmount();
    localStorage.clear();
    renderTour();
    fireEvent.click(screen.getByRole("button", { name: "Pular tutorial" }));
    expect(localStorage.getItem("test-tour")).toBe("completed");
  });

  it("expõe texto alternativo da NFC-e e mantém controles do painel em largura mobile", () => {
    Object.defineProperty(window, "innerWidth", { configurable: true, value: 390 });
    render(<ProductTour storageKey="test-tour" steps={[{
      target: "#create-list",
      title: "Escaneie a nota",
      description: "Leia o QR Code ou digite a chave.",
      illustration: "receipt",
    }]} />);
    expect(screen.getByRole("img", { name: /Exemplo de nota fiscal de consumidor eletrônica/ })).toBeTruthy();
    expect(screen.getByText(/procure o QR Code na parte inferior/)).toBeTruthy();
    expect(screen.getByRole("button", { name: "Pular tutorial" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Concluir" })).toBeTruthy();
  });
});
