import { useEffect, useState } from "react";
import type { ReactNode } from "react";
import { ClientDashboard } from "@/components/dashboard/ClientDashboard";
import { CompanyDashboard } from "@/components/dashboard/CompanyDashboard";
import { AdminDashboard } from "@/components/dashboard/AdminDashboard";
import Auth from "./Auth";
import { useLocation } from "react-router-dom";
import { NotificationToast } from "@/components/notification/NotificationToast";
import { useUser } from "@/contexts/UserContext";
import { DashboardLayout } from "@/components/layout/DashboardLayout";
import { Button } from "@/components/ui/button";
import { ArrowRight, BadgeDollarSign, Check, ListChecks, MapPinned, ShoppingCart } from "lucide-react";

const Index = () => {
  const { user, loading, logout } = useUser();

  const [showNotification, setShowNotification] = useState(false);
  const [authMode, setAuthMode] = useState<"login" | "register" | null>(null);
  const location = useLocation();

  useEffect(() => {

    if (location.state?.fromRegister) {
      setShowNotification(true);
      window.history.replaceState({}, "");
    }
  }, [location]);

  if (loading) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>
    );
  }

  if (!user) {
    if (authMode) {
      return <Auth initialMode={authMode} />;
    }

    return (
      <main className="min-h-screen bg-background text-foreground">
        <header className="mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8">
          <a href="/" className="text-2xl font-black tracking-tight text-primary" aria-label="Oiaí, início">Oiaí</a>
          <Button variant="ghost" onClick={() => setAuthMode("login")}>Entrar</Button>
        </header>

        <section className="mx-auto grid max-w-7xl gap-12 px-5 pb-16 pt-10 sm:px-8 lg:grid-cols-[1.05fr_.95fr] lg:items-center lg:pb-24 lg:pt-20">
          <div>
            <p className="mb-5 inline-flex rounded-full bg-secondary/30 px-3 py-1 text-sm font-semibold text-foreground">
              Monte sua lista. Encontre o menor preço. Economize.
            </p>
            <h1 className="max-w-3xl text-4xl font-black leading-tight tracking-tight sm:text-6xl">
              Compre melhor e gaste menos.
            </h1>
            <p className="mt-6 max-w-xl text-lg leading-8 text-muted-foreground sm:text-xl">
              O Oiaí cria sua lista de compras automaticamente, compara preços e mostra onde encontrar cada produto mais barato — para você economizar de verdade.
            </p>
            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
              <Button size="lg" className="h-12 px-6" onClick={() => setAuthMode("register")}>
                Criar minha conta <ArrowRight className="ml-2 h-4 w-4" aria-hidden="true" />
              </Button>
              <Button size="lg" variant="outline" className="h-12 px-6" onClick={() => setAuthMode("login")}>
                Já tenho uma conta
              </Button>
            </div>
          </div>

          <div className="relative rounded-3xl bg-gradient-primary p-6 shadow-glow sm:p-10" aria-label="Resumo das funcionalidades do Oiaí">
            <div className="rounded-2xl bg-card p-5 shadow-medium sm:p-7">
              <div className="flex items-center gap-3">
                <div className="rounded-xl bg-secondary p-3"><ShoppingCart className="h-6 w-6" aria-hidden="true" /></div>
                <div><p className="font-bold">Lista inteligente</p><p className="text-sm text-muted-foreground">Organizada para economizar</p></div>
              </div>
              <div className="mt-6 space-y-3">
                {["Arroz, feijão e café", "Frutas e verduras", "Itens de limpeza"].map((item) => (
                  <div key={item} className="flex items-center gap-3 rounded-xl border border-border p-3 text-sm">
                    <span className="rounded-full bg-correct p-1"><Check className="h-3 w-3" aria-hidden="true" /></span>{item}
                  </div>
                ))}
              </div>
              <div className="mt-5 grid grid-cols-2 gap-3">
                <div className="rounded-xl bg-correct/20 p-3">
                  <BadgeDollarSign className="h-5 w-5 text-correct-foreground" aria-hidden="true" />
                  <p className="mt-2 text-xs text-muted-foreground">Economia estimada</p>
                  <p className="font-bold text-correct-foreground">R$ 42,80</p>
                </div>
                <div className="rounded-xl bg-secondary/30 p-3">
                  <MapPinned className="h-5 w-5 text-foreground" aria-hidden="true" />
                  <p className="mt-2 text-xs text-muted-foreground">Melhor opção</p>
                  <p className="font-bold">3 locais próximos</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section className="border-t border-border bg-card px-5 py-14 sm:px-8 sm:py-20">
          <div className="mx-auto max-w-7xl">
            <div className="max-w-2xl"><p className="font-semibold text-primary">Como funciona</p><h2 className="mt-2 text-3xl font-black sm:text-4xl">Do começo ao mercado sem complicação.</h2></div>
            <div className="mt-10 grid gap-4 md:grid-cols-3">
              <Feature icon={<ListChecks />} title="Crie sua lista automaticamente" text="Adicione o que precisa e deixe o Oiaí organizar produtos e quantidades para você." />
              <Feature icon={<MapPinned />} title="Encontre onde é mais barato" text="Compare os preços e veja a localização dos lugares onde vale mais a pena comprar." />
              <Feature icon={<BadgeDollarSign />} title="Economize em cada compra" text="Planeje melhor, evite compras desnecessárias e acompanhe quanto pode economizar." />
            </div>
          </div>
        </section>
      </main>
    );
  }

  const renderDashboard = () => {
    switch (user.type) {
      case "client":
        return <ClientDashboard />;
      case "company":
        return <CompanyDashboard />;
      case "admin":
        return <AdminDashboard />;
      default:
        return null;
    }
  };

  return (
    <DashboardLayout>
      {showNotification && (
        <NotificationToast
          message="Enviamos um e-mail com o link de confirmação. Verifique sua caixa de entrada."
          duration={8000}
        />
      )}

      {renderDashboard()}
    </DashboardLayout>
  );
};

function Feature({ icon, title, text }: { icon: ReactNode; title: string; text: string }) {
  return <article className="rounded-2xl border border-border p-6">
    <div className="mb-5 inline-flex rounded-xl bg-primary/10 p-3 text-primary">{icon}</div>
    <h3 className="text-lg font-bold">{title}</h3>
    <p className="mt-2 leading-6 text-muted-foreground">{text}</p>
  </article>;
}

export default Index;
