import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
  Plus, ShoppingCart, MapPin, Star, QrCode, Receipt, Package,
  Table, Edit3, Trash2, Trash, Edit, Code, Library,
  ChevronDown, ChevronUp, Grid, List, Layers
} from "lucide-react";
import { useNavigate } from "react-router-dom";
import api from "@/lib/api";
import { QRCodeModal } from "../modals/QrcodeModal";
import { NotificationToast } from "../notification/NotificationToast";
import { formatarData } from "@/utils/formatters";
import { InvoiceCodeModal } from "../modals/InvoiceCodeModal";
import { Sheet, SheetContent, SheetDescription, SheetTitle, SheetTrigger } from "@/components/ui/sheet";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/components/ui/collapsible";
import { ScrollArea } from "@/components/ui/scroll-area";

interface ItensList {
  id: number;
  name: string;
  favorite: number;
  status: string;
  total: string;
  productsQuantity: number;
  products: any;
}

interface NotificationData {
  message: string;
  type: string;
}

interface DashboardData {
  activeLists: number;
  points: number;
  monthEconomy: number;
  reputation: number;
  recentActivity: RecentActivity[];
}

interface PaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
  from: number;
  to: number;
}

interface RecentActivity {
  id: number;
  created_at: string;
  description: string;
  where: string;
  points: number;
}

const defaultRecentActivity: RecentActivity = {
  id: 0,
  created_at: '',
  description: '',
  where: '',
  points: 0
};

const defaultDashBoardData: DashboardData = {
  activeLists: 0,
  points: 0,
  monthEconomy: 0,
  reputation: 0,
  recentActivity: []
};

const defaultNotificationData: NotificationData = {
  message: '',
  type: 'success'
};

export function ClientDashboard() {
  const navigate = useNavigate();
  const [itensLists, setItensLists] = useState<ItensList[]>([]);
  const [dashboardData, setDashboardData] = useState<DashboardData>(defaultDashBoardData);
  const [paginationMeta, setPaginationMeta] = useState<PaginationMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [showQRModal, setShowQRModal] = useState(false);
  const [showInvoiceCodeModal, setShowInvoiceCodeModal] = useState(false);
  const [isListsSheetOpen, setIsListsSheetOpen] = useState(false);
  const [isQuickActionsOpen, setIsQuickActionsOpen] = useState(false);

  const [notificationData, setNotificationData] = useState<NotificationData>(defaultNotificationData);
  const [showNotification, setShowNotification] = useState(false);

  const handleQRScanClick = () => {
    setShowQRModal(true);
  };

  const handleCodeInsertClick = () => {
    setShowInvoiceCodeModal(true);
  };

  useEffect(() => {
    fetchItensLists();
    fetchDashData();
  }, []);

  const fetchItensLists = async (page: number = 1) => {
    const params: any = { page };
    try {
      const response = await api.get("/lists", { params });
      setItensLists(response.data.data);
    } catch (error) {
      console.error("Error fetching lists:", error);
    } finally {
      setLoading(false);
    }
  };

  const fetchDashData = async () => {
    try {
      const response = await api.get("/dashboard-data");
      setDashboardData(response.data.dashboardData);
    } catch (error) {
      console.error("Error fetching dashboard data:", error);
    }
  };

  const handleDelete = async (listId: number, e: React.MouseEvent) => {
    e.stopPropagation();

    if (!window.confirm('Tem certeza que deseja excluir esta lista?')) {
      return;
    }

    try {
      await api.delete("/lists/" + listId);
      setItensLists(prevLists => prevLists.filter(list => list.id !== listId));
      fetchDashData();
    } catch (error) {
      console.log(error.message);
    }
  };

  const handlePageChange = (page: number) => {
    fetchItensLists(page);
    window.scrollTo(0, 0);
  };

  const handleAddProduct = () => {
    navigate("/products/new");
  };

  // Cards de métricas em grid responsivo
  const MetricsCards = () => (
    <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <Card className="border-0 shadow-soft">
        <CardContent className="p-4 sm:p-6">
          <div className="flex items-center gap-2 sm:gap-3">
            <div className="w-8 h-8 sm:w-12 sm:h-12 bg-primary/10 rounded-lg flex items-center justify-center">
              <ShoppingCart className="w-4 h-4 sm:w-6 sm:h-6 text-primary" />
            </div>
            <div>
              <p className="text-lg sm:text-2xl font-bold">{dashboardData.activeLists || 0}</p>
              <p className="text-xs sm:text-sm text-muted-foreground">Listas Ativas</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card className="border-0 shadow-soft">
        <CardContent className="p-4 sm:p-6">
          <div className="flex items-center gap-2 sm:gap-3">
            <div className="w-8 h-8 sm:w-12 sm:h-12 bg-secondary/10 rounded-lg flex items-center justify-center">
              <Star className="w-4 h-4 sm:w-6 sm:h-6 text-secondary" />
            </div>
            <div>
              <p className="text-lg sm:text-2xl font-bold">{dashboardData.points}</p>
              <p className="text-xs sm:text-sm text-muted-foreground">Pontos</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card className="border-0 shadow-soft">
        <CardContent className="p-4 sm:p-6">
          <div className="flex items-center gap-2 sm:gap-3">
            <div className="w-8 h-8 sm:w-12 sm:h-12 bg-primary/10 rounded-lg flex items-center justify-center">
              <MapPin className="w-4 h-4 sm:w-6 sm:h-6 text-primary" />
            </div>
            <div>
              <p className="text-lg sm:text-2xl font-bold">R$ {dashboardData.monthEconomy}</p>
              <p className="text-xs sm:text-sm text-muted-foreground">Economia</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card className="border-0 shadow-soft">
        <CardContent className="p-4 sm:p-6">
          <div className="flex items-center gap-2 sm:gap-3">
            <div className="w-8 h-8 sm:w-12 sm:h-12 bg-secondary/10 rounded-lg flex items-center justify-center">
              <Receipt className="w-4 h-4 sm:w-6 sm:h-6 text-secondary" />
            </div>
            <div>
              <p className="text-lg sm:text-2xl font-bold">{dashboardData.reputation}</p>
              <p className="text-xs sm:text-sm text-muted-foreground">Reputação</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );

  // Componente das listas para desktop
  const DesktopLists = () => (
    <Card className="border-0 shadow-soft">
      <CardHeader className="flex flex-row items-center justify-between">
        <CardTitle className="text-xl font-bold">Minhas Listas</CardTitle>
        <Button
          onClick={() => navigate("/new-list")}
          className="bg-gradient-primary hover:shadow-glow transition-all duration-300"
          size="sm"
        >
          <Plus className="w-4 h-4 mr-2" />
          Nova Lista
        </Button>
      </CardHeader>
      <CardContent className="space-y-4">
        {itensLists.length === 0 && !loading && (
          <div className="text-center py-8 text-muted-foreground">
            <p>Você ainda não tem nenhuma lista</p>
            <Button
              variant="link"
              onClick={() => navigate("/new-list")}
              className="mt-2"
            >
              Criar sua primeira lista
            </Button>
          </div>
        )}
        {itensLists.map((list) => (
          <div
            key={list.id}
            className="flex items-center justify-between p-4 bg-muted/50 rounded-lg hover:bg-muted transition-colors cursor-pointer"
            onClick={() => navigate(`/list/${list.id}`)}
          >
            <div className="flex flex-col items-start gap-2">
              <CardTitle className="text-lg font-bold">{list.name}</CardTitle>
              <div className="text-xs text-muted-foreground">
                <span>{list.productsQuantity} itens</span>
              </div>
            </div>
            <div className="flex items-center gap-2">
              <Badge variant={list.status !== "active" ? "secondary" : "default"}>
                {list.status !== "active" ? "Concluída" : "Em Andamento"}
              </Badge>
              <Button variant="destructive" size="sm" onClick={(e) => handleDelete(list.id, e)}>
                <Trash2 className="w-4 h-4" />
              </Button>
            </div>
          </div>
        ))}
      </CardContent>
    </Card>
  );

  // Componente das listas para mobile (Sheet)
  const MobileListsSheet = () => (
    <Sheet open={isListsSheetOpen} onOpenChange={setIsListsSheetOpen}>
      <SheetTrigger asChild>
        <Button variant="outline" className="w-full justify-between h-12">
          <div className="flex items-center gap-2">
            <Layers className="w-4 h-4" />
            <span>Minhas Listas</span>
          </div>
          <Badge variant="secondary" className="ml-2">
            {itensLists.length}
          </Badge>
        </Button>
      </SheetTrigger>
      <SheetContent side="bottom" className="h-[80vh] p-0">
        <SheetTitle className="sr-only">Minhas Listas</SheetTitle>
        <SheetDescription className="sr-only">
          Visualize e gerencie suas listas de compras. Você pode criar novas listas,
          editar itens ou excluir listas existentes.
        </SheetDescription>

        <div className="flex flex-col h-full">
          <div className="flex items-center justify-between p-4 border-b">
            <div className="flex items-center gap-2">
              <Layers className="w-5 h-5" />
              <h2 className="text-lg font-bold">Minhas Listas</h2>
            </div>
            <Button
              onClick={() => navigate("/new-list")}
              className="bg-gradient-primary"
              size="sm"
            >
              <Plus className="w-4 h-4 mr-1" />
              Nova
            </Button>
          </div>
          <ScrollArea className="flex-1 p-4">
            <div className="space-y-3">
              {itensLists.length === 0 && !loading && (
                <div className="text-center py-8 text-muted-foreground">
                  <p>Você ainda não tem nenhuma lista</p>
                  <Button
                    variant="link"
                    onClick={() => {
                      navigate("/new-list");
                      setIsListsSheetOpen(false);
                    }}
                    className="mt-2"
                  >
                    Criar sua primeira lista
                  </Button>
                </div>
              )}
              {itensLists.map((list) => (
                <div
                  key={list.id}
                  className="flex items-center justify-between p-4 bg-muted/50 rounded-lg hover:bg-muted transition-colors cursor-pointer"
                  onClick={() => {
                    navigate(`/list/${list.id}`);
                    setIsListsSheetOpen(false);
                  }}
                >
                  <div className="flex flex-col items-start gap-1 flex-1">
                    <span className="font-medium">{list.name}</span>
                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                      <span>{list.productsQuantity} itens</span>
                      <Badge variant={list.status !== "active" ? "secondary" : "default"} className="text-xs">
                        {list.status !== "active" ? "Concluída" : "Em Andamento"}
                      </Badge>
                    </div>
                  </div>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleDelete(list.id, e);
                    }}
                    className="text-destructive hover:text-destructive"
                  >
                    <Trash2 className="w-4 h-4" />
                  </Button>
                </div>
              ))}
            </div>
          </ScrollArea>
        </div>
      </SheetContent>
    </Sheet>
  );

  // Ações rápidas colapsáveis para mobile
  const QuickActionsCollapsible = () => (
    <Collapsible open={isQuickActionsOpen} onOpenChange={setIsQuickActionsOpen} className="lg:hidden">
      <CollapsibleTrigger asChild>
        <Button variant="outline" className="w-full justify-between h-12">
          <div className="flex items-center gap-2">
            <Grid className="w-4 h-4" />
            <span>Ações Rápidas</span>
          </div>
          {isQuickActionsOpen ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
        </Button>
      </CollapsibleTrigger>
      <CollapsibleContent className="mt-3 space-y-2">
        <Button
          variant="outline"
          className="w-full justify-start gap-3 h-12"
          onClick={handleQRScanClick}
        >
          <QrCode className="w-5 h-5" />
          Escanear Nota Fiscal
        </Button>
        <Button
          variant="outline"
          className="w-full justify-start gap-3 h-12"
          onClick={handleCodeInsertClick}
        >
          <Library className="w-5 h-5" />
          Digitar Código da NFCe
        </Button>
        <Button
          variant="outline"
          className="w-full justify-start gap-3 h-12"
          onClick={handleAddProduct}
        >
          <Plus className="w-5 h-5" />
          Adicionar Produto
        </Button>
        <Button
          variant="outline"
          className="w-full justify-start gap-3 h-12"
        >
          <MapPin className="w-5 h-5" />
          Otimizar Rota
        </Button>
      </CollapsibleContent>
    </Collapsible>
  );

  // Ações rápidas para desktop
  const DesktopQuickActions = () => (
    <Card className="border-0 shadow-soft hidden lg:block">
      <CardHeader>
        <CardTitle>Ações Rápidas</CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">
        <Button
          variant="outline"
          className="w-full justify-start gap-3 h-12"
          onClick={handleQRScanClick}
        >
          <QrCode className="w-5 h-5" />
          Escanear Nota Fiscal
        </Button>
        <Button
          variant="outline"
          className="w-full justify-start gap-3 h-12"
          onClick={handleCodeInsertClick}
        >
          <Library className="w-5 h-5" />
          Digitar Código da NFCe
        </Button>
        <Button
          variant="outline"
          className="w-full justify-start gap-3 h-12"
          onClick={handleAddProduct}
        >
          <Plus className="w-5 h-5" />
          Adicionar Produto
        </Button>
        <Button
          variant="outline"
          className="w-full justify-start gap-3 h-12"
        >
          <MapPin className="w-5 h-5" />
          Otimizar Rota
        </Button>
      </CardContent>
    </Card>
  );

  return (
    <div className="container mx-auto px-4 py-4 sm:py-6 space-y-4 sm:space-y-6">
      {showNotification && (
        <NotificationToast
          message={notificationData.message}
          type={notificationData.type}
          onClose={() => setShowNotification(false)}
        />
      )}

      {/* Métricas - sempre visíveis em grid responsivo */}
      <MetricsCards />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        {/* Coluna esquerda: Listas de Compra */}
        <div className="lg:col-span-2 space-y-4">
          {/* Desktop: Cards normais */}
          <div className="hidden lg:block">
            <DesktopLists />
          </div>

          {/* Mobile: Botão com Sheet para listas */}
          <div className="lg:hidden">
            <MobileListsSheet />
          </div>
        </div>

        {/* Coluna direita: Ações Rápidas e Atividade Recente */}
        <div className="space-y-4 sm:space-y-6">
          {/* Desktop: Ações Rápidas fixas */}
          <DesktopQuickActions />

          {/* Mobile: Ações Rápidas colapsáveis */}
          <QuickActionsCollapsible />

          {/* Atividade Recente - mantida em todos os dispositivos */}
          <Card className="border-0 shadow-soft">
            <CardHeader>
              <CardTitle className="text-lg sm:text-xl">Atividade Recente</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {dashboardData.recentActivity.length === 0 && (
                <div className="text-center py-8 text-muted-foreground">
                  <p>Nenhuma atividade recente</p>
                </div>
              )}
              {dashboardData.recentActivity.map((activity) => (
                <div key={activity.id} className="space-y-1 p-2 hover:bg-muted/50 rounded-lg transition-colors">
                  <p className="text-sm font-medium">{activity.description}</p>
                  <div className="flex items-center justify-between text-xs text-muted-foreground">
                    <span>{formatarData(activity.created_at)}</span>
                    <Badge variant="secondary" className="text-xs">
                      +{activity.points} pts
                    </Badge>
                  </div>
                  {activity.where && (
                    <p className="text-xs text-muted-foreground">{activity.where}</p>
                  )}
                </div>
              ))}
            </CardContent>
          </Card>
        </div>
      </div>

      {/* Modais */}
      <QRCodeModal
        isOpen={showQRModal}
        onClose={() => setShowQRModal(false)}
        onSuccess={(data) => {
          setShowNotification(true);
          setNotificationData({
            message: 'QRCode validado com sucesso!',
            type: 'success'
          });
          fetchDashData(); // Atualiza dados após sucesso
        }}
        onError={(error) => {
          setShowNotification(true);
          setNotificationData({
            message: 'QRCode inválido: ' + error,
            type: 'error'
          });
        }}
      />

      <InvoiceCodeModal
        isOpen={showInvoiceCodeModal}
        onClose={() => setShowInvoiceCodeModal(false)}
        onSuccess={(data) => {
          setShowNotification(true);
          setNotificationData({
            message: data?.message || 'Código validado com sucesso!',
            type: 'success'
          });
          fetchDashData(); // Atualiza dados após sucesso
        }}
        onError={(error) => {
          setShowNotification(true);
          setNotificationData({
            message: 'Código inválido: ' + error,
            type: 'error'
          });
        }}
      />
    </div>
  );
}
