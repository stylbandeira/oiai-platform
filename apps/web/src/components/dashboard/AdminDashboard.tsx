import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
  Users, Building2, Package, TrendingUp, Star, MapPin, BarChart3,
  Loader2, MoreVertical, ChevronDown, ChevronUp, Crown, Award, ShoppingBag
} from "lucide-react";
import api from "@/lib/api";
import { useNavigate } from "react-router-dom";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/components/ui/collapsible";

interface DashBoardData {
  systemStats: {
    totalUsers: number,
    totalCompanies: number,
    totalProducts: number,
    systemHealth: number,
  };

  topUsers: Array<{
    id: number;
    name: string;
    points: number;
    reputation: number;
  }>;
  topProducts: Array<{
    id: number;
    name: string;
    registrations: number;
  }>;
  topStores: Array<{
    id: number;
    name: string;
    mentions: number;
  }>;
}

export function AdminDashboard() {
  const [data, setData] = useState<DashBoardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [isTopUsersOpen, setIsTopUsersOpen] = useState(false);
  const navigate = useNavigate();

  const fetchDashboardData = async () => {
    try {
      setLoading(true);
      const response = await api.get('/admin/dashboard');
      setData(response.data);
    } catch (error: any) {
      setError(error.response?.data?.message || 'Erro ao carregar dashboard');
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    fetchDashboardData();
  }, []);

  if (loading) {
    return (
      <div className="container mx-auto px-4 py-6 flex items-center justify-center min-h-[60vh]">
        <Loader2 className="w-8 h-8 animate-spin text-primary" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="container mx-auto px-4 py-6">
        <div className="text-center text-red-500 bg-red-50 p-4 rounded-lg">{error}</div>
      </div>
    );
  }

  if (!data) return null;

  return (
    <div className="container mx-auto px-4 py-4 sm:py-6 space-y-4 sm:space-y-6">
      {/* Estatísticas do Sistema - Em linha no mobile */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-4">
        <Card className="border-0 shadow-sm bg-white">
          <CardContent className="p-3 sm:p-6">
            <div className="flex items-center gap-2 sm:gap-3">
              <div className="w-8 h-8 sm:w-12 sm:h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                <Users className="w-4 h-4 sm:w-6 sm:h-6 text-primary" />
              </div>
              <div>
                <p className="text-base sm:text-2xl font-bold">{data.systemStats.totalUsers.toLocaleString()}</p>
                <p className="text-[10px] sm:text-sm text-muted-foreground">Usuários</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="border-0 shadow-sm bg-white">
          <CardContent className="p-3 sm:p-6">
            <div className="flex items-center gap-2 sm:gap-3">
              <div className="w-8 h-8 sm:w-12 sm:h-12 bg-secondary/10 rounded-lg flex items-center justify-center">
                <Building2 className="w-4 h-4 sm:w-6 sm:h-6 text-secondary" />
              </div>
              <div>
                <p className="text-base sm:text-2xl font-bold">{data.systemStats.totalCompanies}</p>
                <p className="text-[10px] sm:text-sm text-muted-foreground">Empresas</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="border-0 shadow-sm bg-white">
          <CardContent className="p-3 sm:p-6">
            <div className="flex items-center gap-2 sm:gap-3">
              <div className="w-8 h-8 sm:w-12 sm:h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                <Package className="w-4 h-4 sm:w-6 sm:h-6 text-primary" />
              </div>
              <div>
                <p className="text-base sm:text-2xl font-bold">{data.systemStats.totalProducts.toLocaleString()}</p>
                <p className="text-[10px] sm:text-sm text-muted-foreground">Produtos</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="border-0 shadow-sm bg-white">
          <CardContent className="p-3 sm:p-6">
            <div className="flex items-center gap-2 sm:gap-3">
              <div className="w-8 h-8 sm:w-12 sm:h-12 bg-secondary/10 rounded-lg flex items-center justify-center">
                <TrendingUp className="w-4 h-4 sm:w-6 sm:h-6 text-secondary" />
              </div>
              <div>
                <p className="text-base sm:text-2xl font-bold">{data.systemStats.systemHealth}%</p>
                <p className="text-[10px] sm:text-sm text-muted-foreground">Saúde</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Menu Administrativo - Logo abaixo dos cards */}
      <Card className="border-0 shadow-sm bg-white overflow-hidden">
        <CardContent className="p-4 sm:p-6">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                <MoreVertical className="w-5 h-5 text-primary" />
              </div>
              <div>
                <h3 className="font-semibold text-lg">Menu Administrativo</h3>
                <p className="text-sm text-muted-foreground">Gerencie usuários, empresas, produtos e relatórios</p>
              </div>
            </div>

            <div className="flex flex-wrap gap-2 w-full sm:w-auto">
              <Button
                variant="outline"
                size="sm"
                onClick={() => navigate('/admin/users')}
                className="flex-1 sm:flex-initial"
              >
                <Users className="w-4 h-4 mr-2" />
                Usuários
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => navigate('/admin/companies')}
                className="flex-1 sm:flex-initial"
              >
                <Building2 className="w-4 h-4 mr-2" />
                Empresas
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => navigate('/admin/products')}
                className="flex-1 sm:flex-initial"
              >
                <Package className="w-4 h-4 mr-2" />
                Produtos
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => navigate('/admin/reports')}
                className="flex-1 sm:flex-initial"
              >
                <BarChart3 className="w-4 h-4 mr-2" />
                Relatórios
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Layout Principal */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        {/* Coluna da Esquerda - Rankings (2 colunas no desktop) */}
        <div className="lg:col-span-2 space-y-4 sm:space-y-6">
          {/* Produtos Mais Cadastrados */}
          <Card className="border-0 shadow-lg overflow-hidden">
            <CardHeader className="bg-gradient-to-r from-slate-50 to-white border-b">
              <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <CardTitle className="text-lg sm:text-xl font-bold flex items-center gap-2">
                  <ShoppingBag className="w-5 h-5 text-primary" />
                  Produtos Mais Cadastrados
                </CardTitle>
                <Button variant="outline" size="sm" className="w-full sm:w-auto">
                  <BarChart3 className="w-4 h-4 mr-2" />
                  Ver Relatório
                </Button>
              </div>
            </CardHeader>
            <CardContent className="p-4 sm:p-6 space-y-3">
              {data.topProducts.map((product, index) => (
                <div
                  key={product.id}
                  className="flex items-center justify-between p-3 bg-muted/30 hover:bg-muted/50 rounded-lg transition-colors"
                >
                  <div className="flex items-center gap-3 flex-1 min-w-0">
                    <div className={`w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 ${index === 0 ? 'bg-yellow-100 text-yellow-600' :
                      index === 1 ? 'bg-gray-100 text-gray-600' :
                        index === 2 ? 'bg-orange-100 text-orange-600' :
                          'bg-primary/10 text-primary'
                      }`}>
                      {index === 0 ? <Crown className="w-4 h-4" /> :
                        index === 1 ? <Award className="w-4 h-4" /> :
                          <span className="text-sm font-bold">{index + 1}</span>}
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="font-medium truncate">{product.name}</p>
                      <p className="text-xs text-muted-foreground">
                        {product.registrations} cadastros
                      </p>
                    </div>
                  </div>
                  <Badge variant="secondary" className="ml-2 flex-shrink-0">
                    {product.registrations}
                  </Badge>
                </div>
              ))}
              {data.topProducts.length === 0 && (
                <div className="text-center py-8 text-muted-foreground">
                  Nenhum produto cadastrado ainda
                </div>
              )}
            </CardContent>
          </Card>

          {/* Estabelecimentos Mais Citados */}
          <Card className="border-0 shadow-lg overflow-hidden">
            <CardHeader className="bg-gradient-to-r from-slate-50 to-white border-b">
              <CardTitle className="text-lg sm:text-xl font-bold flex items-center gap-2">
                <MapPin className="w-5 h-5 text-secondary" />
                Estabelecimentos Mais Citados
              </CardTitle>
            </CardHeader>
            <CardContent className="p-4 sm:p-6 space-y-3">
              {data.topStores.map((store, index) => (
                <div
                  key={store.id}
                  className="flex items-center justify-between p-3 bg-muted/30 hover:bg-muted/50 rounded-lg transition-colors"
                >
                  <div className="flex items-center gap-3 flex-1 min-w-0">
                    <div className="w-8 h-8 bg-secondary/10 rounded-lg flex items-center justify-center flex-shrink-0">
                      <MapPin className="w-4 h-4 text-secondary" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="font-medium truncate">{store.name}</p>
                      <p className="text-xs text-muted-foreground">
                        {store.mentions} menções
                      </p>
                    </div>
                  </div>
                  <Badge variant="secondary" className="ml-2 flex-shrink-0">
                    {store.mentions}
                  </Badge>
                </div>
              ))}
              {data.topStores.length === 0 && (
                <div className="text-center py-8 text-muted-foreground">
                  Nenhum estabelecimento citado ainda
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        {/* Coluna da Direita - Usuários com Maior Reputação */}
        <div className="space-y-4 sm:space-y-6">
          <Card className="border-0 shadow-lg overflow-hidden">
            <CardHeader className="bg-gradient-to-r from-slate-50 to-white border-b">
              <CardTitle className="text-lg sm:text-xl font-bold flex items-center gap-2">
                <Star className="w-5 h-5 text-secondary fill-secondary" />
                Usuários com Maior Reputação
              </CardTitle>
            </CardHeader>
            <CardContent className="p-4 sm:p-6 space-y-3">
              {data.topUsers.slice(0, 5).map((user, index) => (
                <div
                  key={user.id}
                  className="flex items-center justify-between p-3 bg-muted/30 hover:bg-muted/50 rounded-lg transition-colors"
                >
                  <div className="flex items-center gap-3 flex-1 min-w-0">
                    <div className={`w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 ${index === 0 ? 'bg-yellow-100' :
                      index === 1 ? 'bg-gray-100' :
                        index === 2 ? 'bg-orange-100' :
                          'bg-primary/10'
                      }`}>
                      <span className={`text-sm font-bold ${index === 0 ? 'text-yellow-600' :
                        index === 1 ? 'text-gray-600' :
                          index === 2 ? 'text-orange-600' :
                            'text-primary'
                        }`}>
                        {index + 1}
                      </span>
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="font-medium text-sm truncate">{user.name}</p>
                      <div className="flex items-center gap-2 text-xs text-muted-foreground">
                        <Star className="w-3 h-3 fill-secondary text-secondary" />
                        <span>Reputação: {user.reputation}</span>
                        <span>•</span>
                        <span>{user.points ?? 0} pts</span>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
              {data.topUsers.length === 0 && (
                <div className="text-center py-8 text-muted-foreground">
                  Nenhum usuário encontrado
                </div>
              )}
              {data.topUsers.length > 5 && (
                <Collapsible open={isTopUsersOpen} onOpenChange={setIsTopUsersOpen}>
                  <CollapsibleTrigger asChild>
                    <Button variant="ghost" className="w-full mt-2">
                      {isTopUsersOpen ? (
                        <>Ver menos <ChevronUp className="w-4 h-4 ml-2" /></>
                      ) : (
                        <>Ver mais {data.topUsers.length - 5} usuários <ChevronDown className="w-4 h-4 ml-2" /></>
                      )}
                    </Button>
                  </CollapsibleTrigger>
                  <CollapsibleContent className="space-y-3 mt-3">
                    {data.topUsers.slice(5).map((user, index) => (
                      <div
                        key={user.id}
                        className="flex items-center justify-between p-3 bg-muted/30 rounded-lg"
                      >
                        <div className="flex items-center gap-3 flex-1 min-w-0">
                          <div className="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center flex-shrink-0">
                            <span className="text-sm font-bold text-primary">{index + 6}</span>
                          </div>
                          <div className="flex-1 min-w-0">
                            <p className="font-medium text-sm truncate">{user.name}</p>
                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                              <Star className="w-3 h-3 fill-secondary text-secondary" />
                              <span>Reputação: {user.reputation}</span>
                              <span>•</span>
                              <span>{user.points ?? 0} pts</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    ))}
                  </CollapsibleContent>
                </Collapsible>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}