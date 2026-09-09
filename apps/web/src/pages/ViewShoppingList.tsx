import { useEffect, useState, useCallback, useRef } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { ArrowLeft, DollarSign, Edit, Trash2, Share2, Package, Store, MoreVertical, TrendingUp, LocateFixed, Search } from "lucide-react";
import { useNavigate, useParams } from "react-router-dom";
import api from "@/lib/api";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { useUser } from "@/contexts/UserContext";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { ShoppingListProductRow } from "@/components/shopping-list/ShoppingListProductRow";

interface Product {
  id: number;
  name: string;
  average_price: number;
  quantity: number;
  unity_quantity?: number;
  unity?: {
    id: number;
    abbreviation: string;
    name: string;
  };
  category?: {
    id: number;
    name: string;
  };
  img: string | null;
  ean: string;
  description: string;
  completed?: boolean;
}

interface CompanyProduct {
  product: Product;
  average_price: number;
}

interface CompanyGroup {
  company: {
    id: number;
    name: string;
    raw_address: string;
    address: {
      geocode_status: string;
      full_address: string;
      latitude: string;
      longitude: string;
    };
    distance?: number | string;
    isTooFar?: boolean;
  };
  products: CompanyProduct[];
  distance?: number | string;
  isTooFar?: boolean;
}

interface AddressSuggestion {
  place_id: number;
  display_name: string;
  lat: string;
  lon: string;
}

interface ShoppingList {
  id: number;
  name: string;
  favorite: boolean;
  optimized: boolean;
  status: string;
  total: number;
  created_at: string;
  companies: Record<string, CompanyGroup> | [];
  products?: Product[];
  productsQuantity: number;
}

export default function ViewShoppingList() {
  const navigate = useNavigate();
  const { id } = useParams();
  const [shoppingList, setShoppingList] = useState<ShoppingList | null>(null);
  const [loading, setLoading] = useState(true);
  const [completedItems, setCompletedItems] = useState<Set<number>>(new Set());
  const [hasUnsavedChanges, setHasUnsavedChanges] = useState(false);
  const [isOptimizing, setIsOptimizing] = useState(false);
  const [isRecreating, setIsRecreating] = useState(false);
  const [isOptimizeSheetOpen, setIsOptimizeSheetOpen] = useState(false);
  const [latitude, setLatitude] = useState("");
  const [longitude, setLongitude] = useState("");
  const [maxDistance, setMaxDistance] = useState("25");
  const [locationMessage, setLocationMessage] = useState("");
  const [addressQuery, setAddressQuery] = useState("");
  const [addressSuggestions, setAddressSuggestions] = useState<AddressSuggestion[]>([]);
  const [isSearchingAddress, setIsSearchingAddress] = useState(false);
  const skipNextAddressSearch = useRef(false);
  const changedListId = useRef<string | undefined>(undefined);
  const user = useUser();

  useEffect(() => {
    if (skipNextAddressSearch.current) {
      skipNextAddressSearch.current = false;
      return;
    }
    const query = addressQuery.trim();
    if (query.length < 5) {
      setAddressSuggestions([]);
      return;
    }

    const controller = new AbortController();
    const timer = window.setTimeout(async () => {
      setIsSearchingAddress(true);
      try {
        const params = new URLSearchParams({
          q: query,
          format: "jsonv2",
          addressdetails: "1",
          limit: "5",
          countrycodes: "br",
        });
        const response = await fetch(`https://nominatim.openstreetmap.org/search?${params}`, {
          signal: controller.signal,
          headers: { "Accept-Language": "pt-BR" },
        });
        if (!response.ok) throw new Error("Não foi possível pesquisar o endereço.");
        setAddressSuggestions(await response.json());
      } catch (error) {
        if ((error as Error).name !== "AbortError") setAddressSuggestions([]);
      } finally {
        if (!controller.signal.aborted) setIsSearchingAddress(false);
      }
    }, 500);

    return () => {
      window.clearTimeout(timer);
      controller.abort();
    };
  }, [addressQuery]);

  // ARMAZENA AS QUANTIDADES ORIGINAIS (vindas de list.products)
  const [originalQuantities, setOriginalQuantities] = useState<Map<number, number>>(new Map());

  const getProductUnity = (product: Product): string => {
    if (product.unity?.abbreviation) return product.unity.abbreviation;
    if (product.unity?.name) return product.unity.name;
    return 'un';
  };

  const getProductCategory = (product: Product): string => {
    if (product.category?.name) return product.category.name;
    return 'Produto';
  };

  const getProductQuantity = (product: Product): number => {
    return product.quantity || product.unity_quantity || 1;
  };

  const getUniqueProducts = (products: Product[]): Product[] => {
    const uniqueProducts = new Map<number, Product>();
    products.forEach((product) => {
      if (!uniqueProducts.has(product.id)) uniqueProducts.set(product.id, product);
    });
    return Array.from(uniqueProducts.values());
  };

  const getUniqueCompanyProducts = (products: CompanyProduct[]): CompanyProduct[] => {
    const uniqueProducts = new Map<number, CompanyProduct>();
    products.forEach((item) => {
      if (!uniqueProducts.has(item.product.id)) uniqueProducts.set(item.product.id, item);
    });
    return Array.from(uniqueProducts.values());
  };

  const handleCompleteList = async () => {
    const response = await api.put(`/lists/${id}`, {
      status: 'completed'
    });

    window.location.reload();
  }

  const handleRecreateList = async () => {
    if (!shoppingList || isRecreating) return;

    setIsRecreating(true);

    try {
      const uniqueProducts = getUniqueProducts(shoppingList.products || []);
      const products = uniqueProducts.map(product => ({
        product: { id: product.id },
        quantity: originalQuantities.get(product.id) ?? getProductQuantity(product),
      }));

      const response = await api.post("/lists", {
        name: shoppingList.name + '(copy)',
        products,
      });
      const newListId = response.data?.list?.id ?? response.data?.id;

      if (!newListId) {
        throw new Error("A API não retornou o ID da nova lista.");
      }

      navigate(`/list/${newListId}`);
    } catch (error) {
      console.error("Erro ao refazer lista:", error);
    } finally {
      setIsRecreating(false);
    }
  };

  // Carregar dados
  useEffect(() => {
    const loadData = async () => {
      try {
        setLoading(true);
        changedListId.current = undefined;
        setHasUnsavedChanges(false);
        setCompletedItems(new Set());
        const response = await api.get(`/lists/${id}`);
        const data = response.data.list;

        // SALVA AS QUANTIDADES ORIGINAIS (de list.products)
        const quantitiesMap = new Map<number, number>();
        if (data.products && data.products.length > 0) {
          data.products.forEach((product: Product) => {
            const quantity = getProductQuantity(product);
            quantitiesMap.set(product.id, quantity);
          });
        }
        setOriginalQuantities(quantitiesMap);

        setShoppingList({
          id: data.id,
          name: data.name,
          favorite: data.favorite,
          optimized: data.optimized,
          status: data.status,
          total: data.total,
          created_at: data.created_at,
          companies: data.companies || [],
          products: data.products || [],
          productsQuantity: data.productsQuantity,
        });

        if (data.products && data.products.length > 0) {
          const completed = data.products
            .filter((p: Product) => p.completed)
            .map((p: Product) => p.id);
          setCompletedItems(new Set(completed));
        }

        setLoading(false);
      } catch (error) {
        console.error("Erro ao carregar lista:", error);
        setLoading(false);
      }
    };

    loadData();
  }, [id]);

  const saveCompletedItems = useCallback(async () => {
    if (!hasUnsavedChanges || !id || changedListId.current !== id) return;

    try {
      await api.put(`/listItems/${id}`, {
        completed_items: Array.from(completedItems)
      });
      setHasUnsavedChanges(false);
      changedListId.current = undefined;
    } catch (error) {
      console.error("Erro ao salvar:", error);
    }
  }, [completedItems, hasUnsavedChanges, id]);

  useEffect(() => {
    if (!hasUnsavedChanges) return;
    const timer = setTimeout(() => saveCompletedItems(), 2000);
    return () => clearTimeout(timer);
  }, [completedItems, hasUnsavedChanges, saveCompletedItems]);

  const toggleItemComplete = (productId: number) => {
    setCompletedItems(prev => {
      const newSet = new Set(prev);
      if (newSet.has(productId)) {
        newSet.delete(productId);
      } else {
        newSet.add(productId);
      }
      return newSet;
    });
    changedListId.current = id;
    setHasUnsavedChanges(true);
  };

  const hasCompanies = () => {
    return shoppingList?.companies &&
      Array.isArray(shoppingList.companies) === false &&
      Object.keys(shoppingList.companies).length > 0;
  };

  const getDirectProducts = (): Product[] => {
    if (hasCompanies()) return [];
    return getUniqueProducts(shoppingList?.products || []);
  };

  // FUNÇÃO CORRETÍSSIMA: calcula total usando SEMPRE as quantidades originais
  const calculateTotalWithOriginalQuantities = (products: CompanyProduct[]) => {
    return getUniqueCompanyProducts(products).reduce((sum, item) => {
      const productId = item.product.id;
      const quantity = originalQuantities.get(productId) || 1; // USA A QUANTIDADE ORIGINAL
      const price = item.average_price;
      return sum + (price * quantity);
    }, 0);
  };

  // Para quando NÃO tem companies (lista simples)
  const calculateDirectProductsTotal = (products: Product[]) => {
    return products.reduce((sum, product) => {
      const quantity = getProductQuantity(product);
      const price = product.average_price || 0;
      return sum + (price * quantity);
    }, 0);
  };

  const calculateCompletedCount = (products: CompanyProduct[]) => {
    return products.filter(item => completedItems.has(item.product.id)).length;
  };

  const getTotalProgress = () => {
    if (!shoppingList) return 0;

    let allProducts: Product[] = [];

    if (hasCompanies()) {
      const companiesObj = shoppingList.companies as Record<string, CompanyGroup>;
      allProducts = Object.values(companiesObj).flatMap(
        group => group.products.map(p => p.product)
      );
    } else {
      allProducts = getUniqueProducts(shoppingList.products || []);
    }

    if (allProducts.length === 0) return 0;
    return (completedItems.size / allProducts.length) * 100;
  };

  // VALOR ATUAL OTIMIZADO (usa quantidades originais + preços otimizados)
  const getOptimizedTotalValue = () => {
    if (!shoppingList) return 0;

    if (hasCompanies()) {
      const companiesObj = shoppingList.companies as Record<string, CompanyGroup>;
      let total = 0;
      Object.values(companiesObj).forEach(group => {
        total += calculateTotalWithOriginalQuantities(group.products);
      });
      return total;
    } else {
      return calculateDirectProductsTotal(getUniqueProducts(shoppingList.products || []));
    }
  };

  // VALOR SEM OTIMIZAR (usa quantidades originais + preços originais de list.products)
  const getUnoptimizedTotalValue = () => {
    if (!shoppingList) return 0;
    return calculateDirectProductsTotal(getUniqueProducts(shoppingList.products || []));
  };

  const openOptimizeDialog = () => {
    setLatitude("");
    setLongitude("");
    setAddressQuery("");
    setAddressSuggestions([]);
    setMaxDistance("25");
    setLocationMessage("Solicitando sua localização...");

    const openDialog = () => setIsOptimizeSheetOpen(true);
    if (!navigator.geolocation) {
      setLocationMessage("A localização não está disponível. Pesquise um endereço ou informe as coordenadas.");
      openDialog();
      return;
    }

    navigator.geolocation.getCurrentPosition(
      ({ coords }) => {
        setLatitude(String(coords.latitude));
        setLongitude(String(coords.longitude));
        setLocationMessage("Localização atual preenchida automaticamente.");
        openDialog();
      },
      () => {
        setLocationMessage("A localização não foi autorizada. Pesquise um endereço ou informe as coordenadas.");
        openDialog();
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 },
    );
  };

  const selectAddress = (suggestion: AddressSuggestion) => {
    skipNextAddressSearch.current = true;
    setAddressQuery(suggestion.display_name);
    setLatitude(suggestion.lat);
    setLongitude(suggestion.lon);
    setAddressSuggestions([]);
    setLocationMessage("Coordenadas preenchidas a partir do endereço selecionado.");
  };

  const optimizeRoute = async () => {
    if (isOptimizing) return;

    const parsedLatitude = Number(latitude);
    const parsedLongitude = Number(longitude);
    const parsedDistance = Number(maxDistance);
    if (!latitude.trim() || !longitude.trim() ||
      !Number.isFinite(parsedLatitude) || parsedLatitude < -90 || parsedLatitude > 90 ||
      !Number.isFinite(parsedLongitude) || parsedLongitude < -180 || parsedLongitude > 180) {
      setLocationMessage("Informe uma latitude e longitude válidas ou selecione um endereço.");
      return;
    }
    if (!Number.isFinite(parsedDistance) || parsedDistance <= 0) {
      setLocationMessage("A distância máxima deve ser maior que zero.");
      return;
    }

    setIsOptimizing(true);

    try {
      await api.post(`/lists/${id}/optimize`, {
        latitude: parsedLatitude,
        longitude: parsedLongitude,
        distance: parsedDistance,
      });

      const reloadResponse = await api.get(`/lists/${id}`);
      const newData = reloadResponse.data.list;

      setShoppingList({
        id: newData.id,
        name: newData.name,
        favorite: newData.favorite,
        optimized: newData.optimized,
        status: newData.status,
        total: newData.total,
        created_at: newData.created_at,
        companies: newData.companies || [],
        products: newData.products || [],
        productsQuantity: newData.productsQuantity,
      });
      setIsOptimizeSheetOpen(false);

    } catch (error) {
      console.error("Erro ao otimizar:", error);
      setLocationMessage("Não foi possível otimizar a rota. Tente novamente.");
    } finally {
      setIsOptimizing(false);
    }
  };

  const formatDistance = (distance?: number | string) => {
    const value = Number(distance);
    if (!Number.isFinite(value)) return null;
    if (value < 1) return `${Math.round(value * 1000).toLocaleString("pt-BR")} m`;
    return `${value.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} Km`;
  };

  const handleDelete = async () => {
    try {
      await api.delete(`/lists/${id}`);
      navigate("/");
    } catch (error) {
      console.error("Erro ao deletar:", error);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center">
        <div className="text-center">
          <Package className="w-12 h-12 animate-pulse mx-auto mb-4 text-primary" />
          <p className="text-muted-foreground">Carregando lista...</p>
        </div>
      </div>
    );
  }

  if (!shoppingList) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center">
        <div className="text-center">
          <p className="text-muted-foreground">Lista não encontrada</p>
          <Button onClick={() => navigate("/")} className="mt-4">
            Voltar
          </Button>
        </div>
      </div>
    );
  }

  const hasCompaniesData = hasCompanies();
  const totalProgress = getTotalProgress();
  const optimizedTotal = getOptimizedTotalValue();
  const unoptimizedTotal = getUnoptimizedTotalValue();
  const totalItems = shoppingList.productsQuantity;
  const directProducts = getDirectProducts();
  const isCompleted = shoppingList.status === "completed";

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
      <div className="container mx-auto px-4 py-4 sm:py-6 space-y-4 sm:space-y-6">
        {/* Header - Versão mais sutil e elegante */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2 sm:gap-3">
            <Button
              variant="ghost"
              size="sm"
              onClick={() => navigate("/")}
              className="h-8 w-8 sm:h-9 sm:w-9 p-0"
            >
              <ArrowLeft className="w-4 h-4" />
            </Button>
            <div>
              <h1 className="text-lg sm:text-2xl font-semibold tracking-tight">
                {shoppingList.name}
              </h1>
              <p className="text-xs text-muted-foreground">
                {new Date(shoppingList.created_at).toLocaleDateString('pt-BR')}
              </p>
            </div>
          </div>

          {/* Menu de ações - Mais compacto em mobile */}
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                <MoreVertical className="w-4 h-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48">
              {!isCompleted && (
                <DropdownMenuItem onClick={() => navigate(`/list/${shoppingList.id}/edit`)}>
                  <Edit className="w-4 h-4 mr-2" />
                  Editar Lista
                </DropdownMenuItem>
              )}
              <DropdownMenuItem>
                <Share2 className="w-4 h-4 mr-2" />
                Compartilhar
              </DropdownMenuItem>
              <DropdownMenuItem onClick={handleDelete} className="text-destructive">
                <Trash2 className="w-4 h-4 mr-2" />
                Excluir
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>

        {/* Stats Cards - Em linha no mobile */}
        <div className="grid grid-cols-3 gap-2 sm:gap-4">
          {/* Card Valor Total */}
          <Card className="border-0 shadow-sm bg-white">
            <CardContent className="p-3 sm:p-6">
              <div className="flex flex-col items-start gap-1 sm:gap-2">
                <div className="w-6 h-6 sm:w-10 sm:h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                  <DollarSign className="w-3 h-3 sm:w-5 sm:h-5 text-emerald-600" />
                </div>
                <div>
                  <p className="text-sm sm:text-2xl font-bold">R$ {optimizedTotal.toFixed(2)}</p>
                  <p className="text-[10px] sm:text-sm text-muted-foreground">Valor Total</p>
                  {shoppingList.optimized && unoptimizedTotal > 0 && (
                    <p className="text-[8px] sm:text-xs text-muted-foreground line-through hidden sm:block">
                      R$ {unoptimizedTotal.toFixed(2)}
                    </p>
                  )}
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Card Itens Comprados */}
          <Card className="border-0 shadow-sm bg-white">
            <CardContent className="p-3 sm:p-6">
              <div className="flex flex-col items-start gap-1 sm:gap-2">
                <div className="w-6 h-6 sm:w-10 sm:h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                  <Package className="w-3 h-3 sm:w-5 sm:h-5 text-blue-600" />
                </div>
                <div>
                  <p className="text-sm sm:text-2xl font-bold">{completedItems.size}/{totalItems}</p>
                  <p className="text-[10px] sm:text-sm text-muted-foreground">Itens</p>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Card Progresso */}
          <Card className="border-0 shadow-sm bg-white">
            <CardContent className="p-3 sm:p-6">
              <div className="flex flex-col items-start gap-1 sm:gap-2">
                <div className="w-6 h-6 sm:w-10 sm:h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                  <span className="text-xs sm:text-lg font-bold text-purple-600">{Math.round(totalProgress)}%</span>
                </div>
                <div className="w-full">
                  <div className="w-full h-1 sm:h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div
                      className="h-full bg-purple-500 transition-all duration-300"
                      style={{ width: `${totalProgress}%` }}
                    />
                  </div>
                  <p className="text-[10px] sm:text-sm text-muted-foreground mt-1">Progresso</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {user.user && !isCompleted && (
          <Card className="border-0 shadow-sm bg-white">
            <CardContent className="p-4 sm:p-6">
              <Button
                onClick={openOptimizeDialog}
                className="w-full bg-gradient-to-r from-primary to-primary/80 hover:shadow-lg transition-all"
                disabled={isOptimizing}
              >
                <TrendingUp className="w-4 h-4 mr-2" />
                {shoppingList.optimized ? "Re-Otimizar" : "Otimizar Rota"}
              </Button>
            </CardContent>
          </Card>
        )}

        <Dialog open={isOptimizeSheetOpen} onOpenChange={setIsOptimizeSheetOpen}>
          <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
            <DialogHeader>
              <DialogTitle>{shoppingList.optimized ? "Re-Otimizar rota" : "Otimizar rota"}</DialogTitle>
              <DialogDescription>
                Defina o ponto de origem e a distância máxima para ordenar as empresas.
              </DialogDescription>
            </DialogHeader>

            <div className="space-y-4">
              <div className="rounded-lg bg-muted p-3 text-sm text-muted-foreground">
                <LocateFixed className="mr-2 inline h-4 w-4" />
                {locationMessage}
              </div>

              <div className="space-y-2">
                <Label htmlFor="origin-address">Pesquisar endereço completo</Label>
                <div className="relative">
                  <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                  <Input
                    id="origin-address"
                    value={addressQuery}
                    onChange={(event) => setAddressQuery(event.target.value)}
                    placeholder="Rua, número, bairro, cidade e estado"
                    className="pl-9"
                    autoComplete="off"
                  />
                  {(isSearchingAddress || addressSuggestions.length > 0) && (
                    <div className="absolute z-20 mt-1 w-full overflow-hidden rounded-md border bg-background shadow-lg">
                      {isSearchingAddress ? (
                        <p className="p-3 text-sm text-muted-foreground">Pesquisando...</p>
                      ) : addressSuggestions.map((suggestion) => (
                        <button
                          type="button"
                          key={suggestion.place_id}
                          onClick={() => selectAddress(suggestion)}
                          className="block w-full border-b p-3 text-left text-sm last:border-0 hover:bg-muted"
                        >
                          {suggestion.display_name}
                        </button>
                      ))}
                    </div>
                  )}
                </div>
                <p className="text-xs text-muted-foreground">
                  Busca por <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer" className="underline">OpenStreetMap</a>.
                </p>
              </div>

              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="origin-latitude">Latitude *</Label>
                  <Input id="origin-latitude" type="number" step="any" value={latitude} onChange={(event) => setLatitude(event.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="origin-longitude">Longitude *</Label>
                  <Input id="origin-longitude" type="number" step="any" value={longitude} onChange={(event) => setLongitude(event.target.value)} />
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="max-distance">Distância máxima (Km) *</Label>
                <Input id="max-distance" type="number" min="0.1" step="0.1" value={maxDistance} onChange={(event) => setMaxDistance(event.target.value)} />
              </div>
            </div>

            <DialogFooter>
              <Button variant="outline" onClick={() => setIsOptimizeSheetOpen(false)} disabled={isOptimizing}>Cancelar</Button>
              <Button onClick={optimizeRoute} disabled={isOptimizing}>
                {isOptimizing ? "Otimizando..." : "OK"}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        {/* Products List - Mantido igual */}
        <div className="space-y-6">
          {hasCompaniesData ? (
            Object.values(shoppingList.companies as Record<string, CompanyGroup>)
              .sort((a, b) => {
                const aIsTooFar = a.isTooFar ?? a.company.isTooFar ?? false;
                const bIsTooFar = b.isTooFar ?? b.company.isTooFar ?? false;

                if (aIsTooFar !== bIsTooFar) return aIsTooFar ? 1 : -1;

                const aDistance = Number(a.distance ?? a.company.distance);
                const bDistance = Number(b.distance ?? b.company.distance);
                const safeADistance = Number.isFinite(aDistance) ? aDistance : Number.POSITIVE_INFINITY;
                const safeBDistance = Number.isFinite(bDistance) ? bDistance : Number.POSITIVE_INFINITY;

                return safeADistance - safeBDistance;
              })
              .map((group) => {
              const companyTotal = calculateTotalWithOriginalQuantities(group.products);
              const companyProducts = getUniqueCompanyProducts(group.products);
              const companyCompleted = calculateCompletedCount(companyProducts);
              const isTooFar = group.isTooFar ?? group.company.isTooFar ?? false;
              const companyDistance = formatDistance(group.distance ?? group.company.distance);

              return (
                <Card
                  key={group.company.id}
                  className={`border-0 shadow-lg overflow-hidden transition-colors ${isTooFar ? "bg-slate-100/80 text-slate-500 grayscale-[35%]" : "bg-white"}`}
                >
                  <CardHeader className={isTooFar ? "bg-slate-200/70 border-b border-slate-300" : "bg-gradient-to-r from-slate-50 to-white border-b"}>
                    <CardTitle className="flex flex-wrap items-center justify-between gap-2">
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                          <Store className="w-5 h-5 text-primary" />
                        </div>
                        <div>
                          <h3 className="text-lg font-semibold">{group.company.name}</h3>
                          <div className="flex flex-wrap items-center gap-2">
                            {companyDistance && (
                              <span className="text-sm font-medium">{companyDistance}</span>
                            )}
                            {isTooFar && <Badge variant="outline">Além da distância máxima</Badge>}
                          </div>
                          {group.company.address && (
                            <>
                              {group.company.address.geocode_status === 'done' && (
                                <a
                                  href={`https://www.google.com/maps/search/?api=1&query=${group.company.address.latitude},${group.company.address.longitude}`}
                                  target="_blank"
                                  rel="noopener noreferrer"
                                  className="text-blue-500 underline text-xs"
                                >
                                  Ver no mapa
                                </a>
                              )}
                              <p className="text-xs text-muted-foreground">{group.company.address.full_address}</p>
                            </>
                          )}
                        </div>
                      </div>
                      <Badge variant="secondary" className="text-sm">
                        {companyCompleted}/{companyProducts.length} concluídos
                      </Badge>
                    </CardTitle>
                  </CardHeader>

                  <CardContent className="p-6 space-y-3">
                    {companyProducts.map((item) => {
                      const product = item.product;
                      const unity = getProductUnity(product);
                      const category = getProductCategory(product);
                      const quantity = originalQuantities.get(product.id) || getProductQuantity(product);

                      return (
                        <ShoppingListProductRow
                          key={product.id}
                          name={product.name}
                          image={product.img}
                          quantity={quantity}
                          unity={unity}
                          category={category}
                          unitPrice={item.average_price}
                          completed={completedItems.has(product.id)}
                          canComplete={!isCompleted}
                          onToggleComplete={() => toggleItemComplete(product.id)}
                        />
                      );
                    })}

                    <div className="border-t pt-4 mt-4">
                      <div className="flex justify-between items-center font-semibold">
                        <span>Subtotal</span>
                        <span className="text-primary text-lg">R$ {companyTotal.toFixed(2)}</span>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              );
            })
          ) : directProducts.length > 0 ? (
            <Card className="border-0 shadow-lg overflow-hidden">
              <CardHeader className="bg-gradient-to-r from-slate-50 to-white border-b">
                <CardTitle>
                  <div className="flex items-center gap-3">
                    <Package className="w-5 h-5 text-primary" />
                    <span>Produtos</span>
                  </div>
                </CardTitle>
              </CardHeader>

              <CardContent className="p-6 space-y-3">
                {directProducts.map((product) => {
                  const unity = getProductUnity(product);
                  const category = getProductCategory(product);
                  const quantity = getProductQuantity(product);

                  return (
                    <ShoppingListProductRow
                      key={product.id}
                      name={product.name}
                      image={product.img}
                      quantity={quantity}
                      unity={unity}
                      category={category}
                      unitPrice={product.average_price || 0}
                      completed={completedItems.has(product.id)}
                      canComplete={!isCompleted}
                      onToggleComplete={() => toggleItemComplete(product.id)}
                    />
                  );
                })}

                <div className="border-t pt-4 mt-4">
                  <div className="flex justify-between items-center font-semibold">
                    <span>Total</span>
                    <span className="text-primary text-lg">R$ {calculateDirectProductsTotal(directProducts).toFixed(2)}</span>
                  </div>
                </div>
              </CardContent>
            </Card>
          ) : (
            <Card className="border-0 shadow-lg">
              <CardHeader className="bg-gradient-to-r from-slate-50 to-white border-b">
                <CardTitle>
                  <div className="flex items-center gap-3">
                    <Package className="w-5 h-5 text-primary" />
                    <span>Lista de Compras</span>
                  </div>
                </CardTitle>
              </CardHeader>
              <CardContent className="p-6">
                <p className="text-center text-muted-foreground py-8">
                  Nenhum item encontrado nesta lista
                </p>
              </CardContent>
            </Card>
          )}
        </div>

        {/* Actions - Mantido igual */}
        {user.user && (
          <div className="flex justify-center gap-4 pt-6">
            {!isCompleted && (
              <Button
                variant="outline"
                onClick={() => navigate(`/list/${shoppingList.id}/edit`)}
              >
                <Edit className="w-4 h-4 mr-2" />
                Editar Lista
              </Button>
            )}
            {isCompleted ? (
              <Button
                className="bg-gradient-to-r from-primary to-primary/80 hover:shadow-lg transition-all"
                disabled={isRecreating}
                onClick={handleRecreateList}
              >
                {isRecreating ? "Refazendo Lista..." : "Refazer Lista"}
              </Button>
            ) : (
              <Button
                className="bg-gradient-to-r from-emerald-500 to-emerald-600 hover:shadow-lg transition-all"
                onClick={handleCompleteList}
              >
                Marcar como Concluída
              </Button>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
