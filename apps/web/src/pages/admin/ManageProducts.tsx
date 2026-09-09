import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Checkbox } from "@/components/ui/checkbox";
import {
  Package, Search, Edit3, Trash2, Plus,
  Download, CheckCircle, XCircle, Image as ImageIcon,
} from "lucide-react";
import { useNavigate } from "react-router-dom";
import api from "@/lib/api";
import { CustomPagination } from "@/components/oiai_ui/CustomPagination";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { PageHeader } from "@/components/admin/PageHeader";
import { TableFilters } from "@/components/admin/TableFilters";
import { BulkActionsBar } from "@/components/admin/BulkActionsBar";
import { StatsCards } from "@/components/admin/StatsCards";
import { StandardDialog } from "@/components/ui/standard-dialog";

interface Product {
  id: number;
  name: string;
  sku: string;
  category: string;
  brand?: string;
  description?: string;
  average_price: number;
  img?: string;
  validated: boolean;
  created_at: string;
  updated_at: string;
}

interface Category {
  id: number;
  name: string;
  description: string;
}

interface PaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
  from: number;
  to: number;
}

interface ProductCounts {
  total: number;
  pendentes: number;
  validados: number;
}

const defaultCounts: ProductCounts = {
  total: 0,
  pendentes: 0,
  validados: 0
};

export default function ManageProducts() {
  const navigate = useNavigate();
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [filterCategory, setFilterCategory] = useState<string>("all");
  const [filterStatus, setFilterStatus] = useState<string>("all");
  const [paginationMeta, setPaginationMeta] = useState<PaginationMeta | null>(null);
  const [searchTimeout, setSearchTimeout] = useState<NodeJS.Timeout | null>(null);
  const [categories, setCategories] = useState<Category[]>([]);
  const [counts, setCounts] = useState<ProductCounts>(defaultCounts);
  const [activeTab, setActiveTab] = useState<string>("todos");
  const [selectedProducts, setSelectedProducts] = useState<number[]>([]);
  const [selectAll, setSelectAll] = useState(false);
  const [selectedProductDetail, setSelectedProductDetail] = useState<Product | null>(null);
  const [showValidationDialog, setShowValidationDialog] = useState(false);
  const [validationAction, setValidationAction] = useState<"validate" | "invalidate">("validate");

  useEffect(() => {
    fetchProducts();
    fetchCategories();
  }, []);

  useEffect(() => {
    if (searchTimeout) clearTimeout(searchTimeout);

    const timeout = setTimeout(() => {
      fetchProducts(1, search, filterCategory, filterStatus);
    }, 500);

    setSearchTimeout(timeout);

    return () => {
      if (searchTimeout) clearTimeout(searchTimeout);
    };
  }, [search, filterCategory, filterStatus, activeTab]);

  useEffect(() => {
    setSelectedProducts([]);
    setSelectAll(false);
  }, [activeTab]);

  const fetchProducts = async (
    page: number = 1,
    searchTerm: string = search,
    category: string = filterCategory,
    status: string = filterStatus
  ) => {
    try {
      setLoading(true);
      const params: any = { page };

      if (searchTerm) params.search = searchTerm;
      if (category !== "all") params.category = category;

      if (activeTab === "pendentes") {
        params.validated = false;
      } else if (activeTab === "validados") {
        params.validated = true;
      } else if (status !== "all") {
        params.validated = status === "validados";
      }

      const response = await api.get("/admin/products", { params });

      setProducts(response.data.data || []);
      setPaginationMeta(response.data.meta || null);
      setCounts(response.data.counts || defaultCounts);

    } catch (error) {
      console.error('Erro ao carregar produtos:', error);
      setProducts([]);
      setCounts(defaultCounts);
    } finally {
      setLoading(false);
    }
  };

  const fetchCategories = async () => {
    try {
      const response = await api.get("/categories");
      setCategories(response.data.categories || []);
    } catch (error) {
      console.error('Erro ao carregar categorias:', error);
    }
  };

  const handleDelete = async (productId: number) => {
    if (!confirm("Tem certeza que deseja excluir este produto?")) return;

    try {
      await api.delete(`/admin/products/${productId}`);
      fetchProducts();
    } catch (error) {
      console.error('Erro ao deletar produto:', error);
      alert('Erro ao excluir produto');
    }
  };

  const handleExport = async () => {
    try {
      const response = await api.get("/admin/products/export", {
        responseType: 'blob'
      });

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'produtos.csv');
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      console.error('Erro ao exportar produtos:', error);
    }
  };

  const handleSelectAll = () => {
    if (selectAll) {
      setSelectedProducts([]);
    } else {
      setSelectedProducts(products.map(p => p.id));
    }
    setSelectAll(!selectAll);
  };

  const handleSelectProduct = (productId: number) => {
    setSelectedProducts(prev => {
      const newSelected = prev.includes(productId)
        ? prev.filter(id => id !== productId)
        : [...prev, productId];
      setSelectAll(newSelected.length === products.length);
      return newSelected;
    });
  };

  const handleBulkValidation = async (validate: boolean) => {
    if (selectedProducts.length === 0) return;

    try {
      await api.post("/admin/products/bulk-validate", {
        product_ids: selectedProducts,
        validated: validate
      });

      fetchProducts();
      setSelectedProducts([]);
      setSelectAll(false);
      setShowValidationDialog(false);
    } catch (error) {
      console.error('Erro ao validar produtos:', error);
      alert('Erro ao processar validação');
    }
  };

  const formatPrice = (price: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(price);
  };

  const getImageUrl = (img: string | undefined) => {
    if (!img) return null;
    if (img.startsWith('http')) return img;
    return `${import.meta.env.VITE_API_URL}/storage/${img}`;
  };

  const stats = [
    { label: "Total", value: counts.total, icon: <Package className="w-3 h-3 sm:w-4 sm:h-4 text-white" />, color: "bg-primary" },
    { label: "Pendentes", value: counts.pendentes, icon: <XCircle className="w-3 h-3 sm:w-4 sm:h-4 text-white" />, color: "bg-yellow-500" },
    { label: "Validados", value: counts.validados, icon: <CheckCircle className="w-3 h-3 sm:w-4 sm:h-4 text-white" />, color: "bg-green-500" }
  ];

  const categoryOptions = categories.map(cat => ({ value: cat.name, label: cat.name }));
  const statusOptions = [
    { value: "validados", label: "Validados" },
    { value: "pendentes", label: "Pendentes" }
  ];

  if (loading) {
    return (
      <div className="container mx-auto px-4 py-6">
        <div className="flex items-center justify-center min-h-[60vh]">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-3 sm:px-4 py-4 sm:py-6 space-y-4 sm:space-y-6">
      <PageHeader
        title="Gerenciar Produtos"
        subtitle="Gerencie todos os produtos cadastrados na plataforma"
        actions={[
          { label: "Exportar", icon: <Download className="w-4 h-4 sm:mr-2" />, onClick: handleExport, variant: "outline" },
          { label: "Novo Produto", icon: <Plus className="w-4 h-4 sm:mr-2" />, onClick: () => navigate('/admin/products/new') }
        ]}
      />

      <StatsCards stats={stats} />

      <Tabs defaultValue="todos" value={activeTab} onValueChange={setActiveTab} className="space-y-4">
        <TabsList className="grid w-full grid-cols-3">
          <TabsTrigger value="todos">Todos</TabsTrigger>
          <TabsTrigger value="pendentes">Pendentes</TabsTrigger>
          <TabsTrigger value="validados">Validados</TabsTrigger>
        </TabsList>

        <Card className="border-0 shadow-sm">
          <CardContent className="p-4 sm:p-6">
            <TableFilters
              searchPlaceholder="Buscar por nome, código ou marca..."
              searchValue={search}
              onSearchChange={setSearch}
              filters={[
                {
                  key: "category",
                  placeholder: "Categoria",
                  value: filterCategory,
                  onChange: setFilterCategory,
                  options: categoryOptions
                },
                ...(activeTab === "todos" ? [{
                  key: "status",
                  placeholder: "Status",
                  value: filterStatus,
                  onChange: setFilterStatus,
                  options: statusOptions
                }] : [])
              ]}
            />
          </CardContent>
        </Card>

        <BulkActionsBar
          selectedCount={selectedProducts.length}
          actions={[
            {
              label: "Validar",
              icon: <CheckCircle className="w-4 h-4" />,
              onClick: () => { setValidationAction("validate"); setShowValidationDialog(true); },
              variant: "outline",
              className: "bg-green-50 text-green-700 border-green-200 hover:bg-green-100"
            },
            {
              label: "Invalidar",
              icon: <XCircle className="w-4 h-4" />,
              onClick: () => { setValidationAction("invalidate"); setShowValidationDialog(true); },
              variant: "outline",
              className: "bg-yellow-50 text-yellow-700 border-yellow-200 hover:bg-yellow-100"
            }
          ]}
          onClear={() => { setSelectedProducts([]); setSelectAll(false); }}
        />

        {/* Tabela Desktop */}
        <Card className="border-0 shadow-sm hidden md:block">
          <CardHeader className="pb-2">
            <CardTitle className="flex items-center gap-2 text-base sm:text-lg">
              <Package className="w-4 h-4 sm:w-5 sm:h-5" />
              Produtos ({paginationMeta?.total || 0})
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b">
                    <th className="w-12 py-3 text-left">
                      <Checkbox
                        checked={selectAll}
                        onCheckedChange={handleSelectAll}
                      />
                    </th>
                    <th className="w-20 py-3 text-left">Imagem</th>
                    <th className="py-3 text-left">Produto</th>
                    <th className="w-32 py-3 text-left">Código</th>
                    <th className="w-32 py-3 text-left">Categoria</th>
                    <th className="w-24 py-3 text-left">Preço</th>
                    <th className="w-24 py-3 text-left">Status</th>
                    <th className="w-32 py-3 text-left">Atualização</th>
                    <th className="w-24 py-3 text-right">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  {products.map((product) => (
                    <tr key={product.id} className="border-b hover:bg-muted/50">
                      <td className="py-3">
                        <Checkbox
                          checked={selectedProducts.includes(product.id)}
                          onCheckedChange={() => handleSelectProduct(product.id)}
                        />
                      </td>
                      <td className="py-3">
                        <div className="w-12 h-12 rounded-md overflow-hidden bg-gray-100 border">
                          {product.img ? (
                            <img
                              src={getImageUrl(product.img) || ''}
                              alt={product.name}
                              className="w-full h-full object-cover"
                              onError={(e) => {
                                (e.target as HTMLImageElement).src = '/placeholder-image.png';
                              }}
                            />
                          ) : (
                            <div className="w-full h-full flex items-center justify-center">
                              <ImageIcon className="w-6 h-6 text-gray-400" />
                            </div>
                          )}
                        </div>
                      </td>
                      <td className="py-3">
                        <div className="max-w-[250px]">
                          <p className="font-medium truncate">{product.name}</p>
                          {product.brand && <p className="text-sm text-muted-foreground truncate">{product.brand}</p>}
                        </div>
                      </td>
                      <td className="py-3">
                        <code className="text-xs bg-muted px-2 py-1 rounded whitespace-nowrap">{product.sku || 'N/A'}</code>
                      </td>
                      <td className="py-3">
                        <span className="text-sm whitespace-nowrap">{product.category}</span>
                      </td>
                      <td className="py-3">
                        <span className="font-medium whitespace-nowrap">{formatPrice(product.average_price)}</span>
                      </td>
                      <td className="py-3">
                        {product.validated ? (
                          <Badge className="bg-green-100 text-green-700 whitespace-nowrap">
                            <CheckCircle className="w-3 h-3 mr-1" />
                            Validado
                          </Badge>
                        ) : (
                          <Badge variant="destructive" className="whitespace-nowrap">
                            <XCircle className="w-3 h-3 mr-1" />
                            Pendente
                          </Badge>
                        )}
                      </td>
                      <td className="py-3">
                        <span className="text-sm whitespace-nowrap">
                          {new Date(product.updated_at).toLocaleDateString('pt-BR')}
                        </span>
                      </td>
                      <td className="py-3 text-right">
                        <div className="flex items-center justify-end gap-1">
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => navigate(`/admin/products/edit/${product.id}`)}
                            className="h-8 w-8"
                          >
                            <Edit3 className="w-4 h-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleDelete(product.id)}
                            className="h-8 w-8 text-destructive hover:text-destructive"
                          >
                            <Trash2 className="w-4 h-4" />
                          </Button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {products.length === 0 && !loading && (
              <div className="text-center py-12 text-muted-foreground">
                <Package className="w-12 h-12 mx-auto mb-4 opacity-50" />
                <p>Nenhum produto encontrado</p>
              </div>
            )}

            {paginationMeta && paginationMeta.last_page > 1 && (
              <div className="mt-4">
                <CustomPagination
                  paginationMeta={paginationMeta}
                  search={search}
                  filterStatus={filterStatus}
                  onPageChange={(page: number) => fetchProducts(page)}
                />
              </div>
            )}
          </CardContent>
        </Card>

        {/* Cards Mobile */}
        <div className="md:hidden space-y-3">
          {products.map((product) => (
            <Card key={product.id} className="hover:shadow-md transition-shadow">
              <CardContent className="p-4">
                <div className="flex items-start gap-3">
                  {/* Imagem */}
                  <div className="w-16 h-16 rounded-md overflow-hidden bg-gray-100 border flex-shrink-0">
                    {product.img ? (
                      <img
                        src={getImageUrl(product.img) || ''}
                        alt={product.name}
                        className="w-full h-full object-cover"
                        onError={(e) => {
                          (e.target as HTMLImageElement).src = '/placeholder-image.png';
                        }}
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center">
                        <ImageIcon className="w-8 h-8 text-gray-400" />
                      </div>
                    )}
                  </div>

                  {/* Informações */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-2">
                      <div className="flex-1 min-w-0">
                        <p className="font-semibold truncate">{product.name}</p>
                        {product.brand && <p className="text-sm text-muted-foreground truncate">{product.brand}</p>}
                        <p className="text-xs text-muted-foreground mt-1 truncate">SKU: {product.sku || 'N/A'}</p>
                      </div>
                      <div className="flex-shrink-0">
                        {product.validated ? (
                          <Badge className="bg-green-100 text-green-700 text-xs whitespace-nowrap">
                            <CheckCircle className="w-3 h-3 mr-1" />
                            Validado
                          </Badge>
                        ) : (
                          <Badge variant="destructive" className="text-xs whitespace-nowrap">
                            <XCircle className="w-3 h-3 mr-1" />
                            Pendente
                          </Badge>
                        )}
                      </div>
                    </div>

                    <div className="flex items-center justify-between mt-3">
                      <div className="min-w-0 flex-1">
                        <p className="text-xs text-muted-foreground">Categoria</p>
                        <p className="text-sm truncate">{product.category}</p>
                      </div>
                      <div className="text-right flex-shrink-0 ml-2">
                        <p className="text-xs text-muted-foreground">Preço Médio</p>
                        <p className="text-lg font-bold text-primary">{formatPrice(product.average_price)}</p>
                      </div>
                    </div>

                    {/* Botões de ação no card mobile */}
                    <div className="flex items-center justify-end gap-2 mt-3 pt-3 border-t">
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => navigate(`/admin/products/edit/${product.id}`)}
                      >
                        <Edit3 className="w-4 h-4 mr-1" />
                        Editar
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => handleDelete(product.id)}
                        className="text-destructive hover:text-destructive"
                      >
                        <Trash2 className="w-4 h-4 mr-1" />
                        Excluir
                      </Button>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}

          {products.length === 0 && !loading && (
            <div className="text-center py-12 text-muted-foreground">
              <Package className="w-12 h-12 mx-auto mb-4 opacity-50" />
              <p>Nenhum produto encontrado</p>
            </div>
          )}

          {paginationMeta && paginationMeta.last_page > 1 && (
            <div className="mt-4">
              <CustomPagination
                paginationMeta={paginationMeta}
                search={search}
                filterStatus={filterStatus}
                onPageChange={(page: number) => fetchProducts(page)}
              />
            </div>
          )}
        </div>
      </Tabs>

      {/* Dialog de confirmação */}
      <AlertDialog open={showValidationDialog} onOpenChange={setShowValidationDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              {validationAction === "validate" ? "Validar Produtos" : "Invalidar Produtos"}
            </AlertDialogTitle>
            <AlertDialogDescription>
              Você tem certeza que deseja {validationAction === "validate" ? "validar" : "invalidar"} {selectedProducts.length} produto{selectedProducts.length > 1 ? 's' : ''}?
              {validationAction === "invalidate" && (
                <span className="block mt-2 text-yellow-600">
                  Produtos invalidados não aparecerão para clientes até serem validados novamente.
                </span>
              )}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => handleBulkValidation(validationAction === "validate")}
              className={validationAction === "validate" ? "bg-green-600 hover:bg-green-700" : "bg-yellow-600 hover:bg-yellow-700"}
            >
              {validationAction === "validate" ? "Validar" : "Invalidar"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      <StandardDialog
        open={!!selectedProductDetail}
        onOpenChange={(open) => {
          if (!open) setSelectedProductDetail(null);
        }}
        title="Detalhes do Produto"
        actions={selectedProductDetail && (
          <>
            <Button className="flex-1" onClick={() => navigate(`/admin/products/edit/${selectedProductDetail.id}`)}>
              <Edit3 className="w-4 h-4 mr-2" /> Editar
            </Button>
            <Button variant="destructive" className="flex-1" onClick={() => handleDelete(selectedProductDetail.id)}>
              <Trash2 className="w-4 h-4 mr-2" /> Excluir
            </Button>
          </>
        )}
      >
        {selectedProductDetail && (
            <div className="space-y-4">
              <div className="flex items-center gap-4">
                <div className="w-20 h-20 rounded-md overflow-hidden bg-gray-100 border flex-shrink-0">
                  {selectedProductDetail.img ? (
                    <img
                      src={getImageUrl(selectedProductDetail.img) || ''}
                      alt={selectedProductDetail.name}
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center">
                      <ImageIcon className="w-10 h-10 text-gray-400" />
                    </div>
                  )}
                </div>
                <div className="flex-1 min-w-0">
                  <h3 className="text-xl font-bold truncate">{selectedProductDetail.name}</h3>
                  {selectedProductDetail.brand && (
                    <p className="text-sm text-muted-foreground truncate">{selectedProductDetail.brand}</p>
                  )}
                </div>
              </div>

              <div className="space-y-3">
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <p className="text-xs text-muted-foreground">Código de Barras</p>
                    <p className="font-mono text-sm break-all">{selectedProductDetail.sku || 'N/A'}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Categoria</p>
                    <p className="text-sm">{selectedProductDetail.category}</p>
                  </div>
                </div>

                {selectedProductDetail.description && (
                  <div>
                    <p className="text-xs text-muted-foreground">Descrição</p>
                    <p className="text-sm break-words">{selectedProductDetail.description}</p>
                  </div>
                )}

                <div className="border-t pt-3">
                  <div className="flex justify-between items-center">
                    <div>
                      <p className="text-xs text-muted-foreground">Preço Médio</p>
                      <p className="text-2xl font-bold text-primary">{formatPrice(selectedProductDetail.average_price)}</p>
                    </div>
                    <div>
                      <p className="text-xs text-muted-foreground">Status</p>
                      {selectedProductDetail.validated ? (
                        <Badge className="bg-green-100 text-green-700">Validado</Badge>
                      ) : (
                        <Badge variant="destructive">Pendente</Badge>
                      )}
                    </div>
                  </div>
                </div>
              </div>

            </div>
        )}
      </StandardDialog>
    </div>
  );
}
