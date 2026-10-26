// @vitest-environment jsdom
import { afterEach, describe, expect, it, vi } from "vitest";
import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router-dom";
import Index from "./Index";

const auth = vi.hoisted(() => ({ user: null as null | { type: string }, loading: false }));
vi.mock("@/contexts/UserContext", () => ({ useUser: () => ({ ...auth, logout: vi.fn() }) }));
vi.mock("./Auth", () => ({ default: ({ initialMode }: { initialMode: string }) => <div>Formulário: {initialMode}</div> }));
vi.mock("@/components/dashboard/ClientDashboard", () => ({ ClientDashboard: () => <div>Painel client</div> }));
vi.mock("@/components/dashboard/CompanyDashboard", () => ({ CompanyDashboard: () => <div>Painel company</div> }));
vi.mock("@/components/dashboard/AdminDashboard", () => ({ AdminDashboard: () => <div>Painel admin</div> }));
vi.mock("@/components/layout/DashboardLayout", () => ({ DashboardLayout: ({ children }: { children: React.ReactNode }) => <>{children}</> }));

afterEach(() => {
  cleanup();
  auth.user = null;
  auth.loading = false;
});

describe("landing pública", () => {
  it("carrega sem sessão e abre cadastro pelo CTA principal", () => {
    const { container } = render(<MemoryRouter><Index /></MemoryRouter>);
    expect(screen.getByRole("heading", { name: "Compre melhor e gaste menos." })).toBeTruthy();
    expect(screen.getByRole("link", { name: "Oiaí, início" }).querySelector("img")?.getAttribute("src")).toBe("/logos/logo_secondary.svg");
    expect(screen.getByLabelText("Resumo das funcionalidades do Oiaí").className).toContain("bg-[#B75A4B]");
    expect(container.querySelector('img[src="/oiai-map-home.png"]')?.getAttribute("aria-hidden")).toBe("true");
    expect(screen.getByText(/encontrar cada produto mais barato/)).toBeTruthy();
    fireEvent.click(screen.getByRole("button", { name: /Criar minha conta/ }));
    expect(screen.getByText("Formulário: register")).toBeTruthy();
  });

  it("abre login pelos dois atalhos de visitante", () => {
    const view = render(<MemoryRouter><Index /></MemoryRouter>);
    fireEvent.click(screen.getByRole("button", { name: "Entrar" }));
    expect(screen.getByText("Formulário: login")).toBeTruthy();
    view.unmount();
    render(<MemoryRouter><Index /></MemoryRouter>);
    fireEvent.click(screen.getByRole("button", { name: "Já tenho uma conta" }));
    expect(screen.getByText("Formulário: login")).toBeTruthy();
  });

  it("mostra o produto para usuário autenticado", () => {
    auth.user = { type: "client" };
    render(<MemoryRouter><Index /></MemoryRouter>);
    expect(screen.getByText("Painel client")).toBeTruthy();
    expect(screen.queryByRole("heading", { name: "Compre melhor e gaste menos." })).toBeNull();
  });
});
