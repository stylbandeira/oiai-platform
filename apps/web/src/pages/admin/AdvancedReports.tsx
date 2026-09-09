import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { 
  BarChart3, 
  ArrowLeft, 
  TrendingUp, 
  Users, 
  Package, 
  Building2, 
  Calendar,
  Download,
  Filter
} from "lucide-react";
import { useNavigate } from "react-router-dom";
import api from "@/lib/api";

interface ReportData {
  period: string;
  topProducts: Array<{
    id: number;
    name: string;
    registrations: number;
    trend: 'up' | 'down' | 'stable';
    growth: number;
  }>;
  topUsers: Array<{
    id: number;
    name: string;
    points: number;
    reputation: number;
    contributions: number;
  }>;
  topStores: Array<{
    id: number;
    name: string;
    mentions: number;
    averagePrice: number;
    trend: 'up' | 'down' | 'stable';
  }>;
  systemMetrics: {
    totalInteractions: number;
    priceUpdates: number;
    newRegistrations: number;
    activeUsers: number;
  };
}

export default function AdvancedReports() {
  const navigate = useNavigate();
  const [reportData, setReportData] = useState<ReportData | null>(null);
  const [loading, setLoading] = useState(true);
  const [selectedPeriod, setSelectedPeriod] = useState("30days");
  const [selectedReport, setSelectedReport] = useState("overview");

  useEffect(() => {
    fetchReportData();
  }, [selectedPeriod]);

  const fetchReportData = async () => {
    try {
      setLoading(true);
      // Mock data for now
      setReportData({
        period: selectedPeriod,
        topProducts: [
          {
            id: 1,
            name: "Arroz Branco Tipo 1 5kg",
            registrations: 120,
            trend: "up",
            growth: 15.2
          },
          {
            id: 2,
            name: "Leite Integral 1L",
            registrations: 89,
            trend: "up",
            growth: 8.5
          },
          {
            id: 3,
            name: "Açúcar Cristal 1kg",
            registrations: 75,
            trend: "stable",
            growth: 0.5
          }
        ],
        topUsers: [
          {
            id: 1,
            name: "João Silva",
            points: 850,
            reputation: 4.8,
            contributions: 45
          },
          {
            id: 2,
            name: "Maria Santos",
            points: 720,
            reputation: 4.6,
            contributions: 38
          }
        ],
        topStores: [
          {
            id: 1,
            name: "Supermercado Extra",
            mentions: 234,
            averagePrice: 15.30,
            trend: "up"
          },
          {
            id: 2,
            name: "Mercado São José",
            mentions: 189,
            averagePrice: 14.80,
            trend: "down"
          }
        ],
        systemMetrics: {
          totalInteractions: 15420,
          priceUpdates: 3280,
          newRegistrations: 185,
          activeUsers: 892
        }
      });
    } catch (error) {
      console.error('Erro ao carregar relatórios:', error);
    } finally {
      setLoading(false);
    }
  };

  const getTrendIcon = (trend: string) => {
    if (trend === 'up') return <TrendingUp className="w-4 h-4 text-green-600" />;
    if (trend === 'down') return <TrendingUp className="w-4 h-4 text-red-600 rotate-180" />;
    return <div className="w-4 h-4 bg-gray-400 rounded-full" />;
  };

  const getTrendColor = (trend: string, growth: number) => {
    if (trend === 'up') return "text-green-600";
    if (trend === 'down') return "text-red-600";
    return "text-gray-600";
  };

  const getPeriodLabel = (period: string) => {
    const labels = {
      '7days': 'Últimos 7 dias',
      '30days': 'Últimos 30 dias',
      '90days': 'Últimos 90 dias',
      '1year': 'Último ano'
    };
    return labels[period as keyof typeof labels] || period;
  };

  return (
    <div className="container mx-auto px-4 py-6 space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button
            variant="ghost"
            size="icon"
            onClick={() => navigate('/')}
          >
            <ArrowLeft className="w-5 h-5" />
          </Button>
          <div>
            <h1 className="text-3xl font-bold">Relatórios Avançados</h1>
            <p className="text-muted-foreground">
              Análise detalhada da plataforma
            </p>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <Button variant="outline">
            <Download className="w-4 h-4 mr-2" />
            Exportar
          </Button>
          <Button variant="outline">
            <Filter className="w-4 h-4 mr-2" />
            Filtros
          </Button>
        </div>
      </div>

      {/* Filtros de Período */}
      <Card className="border-0 shadow-soft">
        <CardContent className="p-6">
          <div className="flex flex-col sm:flex-row gap-4">
            <Select value={selectedPeriod} onValueChange={setSelectedPeriod}>
              <SelectTrigger className="w-full sm:w-48">
                <SelectValue placeholder="Período" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="7days">Últimos 7 dias</SelectItem>
                <SelectItem value="30days">Últimos 30 dias</SelectItem>
                <SelectItem value="90days">Últimos 90 dias</SelectItem>
                <SelectItem value="1year">Último ano</SelectItem>
              </SelectContent>
            </Select>
            <Select value={selectedReport} onValueChange={setSelectedReport}>
              <SelectTrigger className="w-full sm:w-48">
                <SelectValue placeholder="Tipo de Relatório" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="overview">Visão Geral</SelectItem>
                <SelectItem value="products">Produtos</SelectItem>
                <SelectItem value="users">Usuários</SelectItem>
                <SelectItem value="stores">Estabelecimentos</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Métricas do Sistema */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="border-0 shadow-soft">
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                <BarChart3 className="w-6 h-6 text-primary" />
              </div>
              <div>
                <p className="text-2xl font-bold">
                  {reportData?.systemMetrics.totalInteractions.toLocaleString()}
                </p>
                <p className="text-sm text-muted-foreground">Interações Totais</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="border-0 shadow-soft">
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 bg-secondary/10 rounded-lg flex items-center justify-center">
                <TrendingUp className="w-6 h-6 text-secondary" />
              </div>
              <div>
                <p className="text-2xl font-bold">
                  {reportData?.systemMetrics.priceUpdates.toLocaleString()}
                </p>
                <p className="text-sm text-muted-foreground">Atualizações de Preço</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="border-0 shadow-soft">
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                <Users className="w-6 h-6 text-primary" />
              </div>
              <div>
                <p className="text-2xl font-bold">
                  {reportData?.systemMetrics.activeUsers}
                </p>
                <p className="text-sm text-muted-foreground">Usuários Ativos</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="border-0 shadow-soft">
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 bg-secondary/10 rounded-lg flex items-center justify-center">
                <Calendar className="w-6 h-6 text-secondary" />
              </div>
              <div>
                <p className="text-2xl font-bold">
                  {reportData?.systemMetrics.newRegistrations}
                </p>
                <p className="text-sm text-muted-foreground">Novos Cadastros</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Produtos Mais Cadastrados */}
        <Card className="border-0 shadow-soft">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Package className="w-5 h-5" />
              Produtos Mais Cadastrados ({getPeriodLabel(selectedPeriod)})
            </CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Produto</TableHead>
                  <TableHead>Cadastros</TableHead>
                  <TableHead>Tendência</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {reportData?.topProducts.map((product, index) => (
                  <TableRow key={product.id}>
                    <TableCell>
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center">
                          <span className="text-sm font-bold">{index + 1}</span>
                        </div>
                        <span className="font-medium">{product.name}</span>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant="secondary">{product.registrations}</Badge>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        {getTrendIcon(product.trend)}
                        <span className={`text-sm ${getTrendColor(product.trend, product.growth)}`}>
                          {product.growth > 0 ? '+' : ''}{product.growth.toFixed(1)}%
                        </span>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        {/* Estabelecimentos Mais Citados */}
        <Card className="border-0 shadow-soft">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Building2 className="w-5 h-5" />
              Estabelecimentos Mais Citados
            </CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Estabelecimento</TableHead>
                  <TableHead>Menções</TableHead>
                  <TableHead>Preço Médio</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {reportData?.topStores.map((store, index) => (
                  <TableRow key={store.id}>
                    <TableCell>
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 bg-secondary/10 rounded-lg flex items-center justify-center">
                          <span className="text-sm font-bold">{index + 1}</span>
                        </div>
                        <span className="font-medium">{store.name}</span>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant="secondary">{store.mentions}</Badge>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <span className="font-medium">R$ {store.averagePrice.toFixed(2)}</span>
                        {getTrendIcon(store.trend)}
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>

      {/* Usuários com Maior Reputação */}
      <Card className="border-0 shadow-soft">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Users className="w-5 h-5" />
            Usuários com Maior Reputação ({getPeriodLabel(selectedPeriod)})
          </CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Usuário</TableHead>
                <TableHead>Pontos</TableHead>
                <TableHead>Reputação</TableHead>
                <TableHead>Contribuições</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {reportData?.topUsers.map((user, index) => (
                <TableRow key={user.id}>
                  <TableCell>
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center">
                        <span className="text-sm font-bold">{index + 1}</span>
                      </div>
                      <span className="font-medium">{user.name}</span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge variant="secondary">{user.points}</Badge>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-1">
                      <span className="font-medium">{user.reputation}</span>
                      <div className="flex">
                        {[...Array(5)].map((_, i) => (
                          <div
                            key={i}
                            className={`w-3 h-3 ${
                              i < Math.floor(user.reputation) 
                                ? "text-secondary" 
                                : "text-gray-300"
                            }`}
                          >
                            ★
                          </div>
                        ))}
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <span className="text-sm text-muted-foreground">
                      {user.contributions} contribuições
                    </span>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}