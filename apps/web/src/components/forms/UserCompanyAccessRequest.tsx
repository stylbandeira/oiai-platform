import { useState, useEffect, useCallback } from "react";
import { debounce } from "lodash";
import api from "@/lib/api";
import { CompanyForm } from "./CompanyForm";
import { Building2, Search, Loader2, Building, AlertCircle, ArrowLeft } from "lucide-react";
import { Button } from "../ui/button";
import { useNavigate } from "react-router-dom";
import ManageCompanies from "@/pages/admin/ManageCompanies";
import { useUser } from "@/contexts/UserContext";
import { CompanySelector } from "../company/CompanySelector";

type Company = {
  id: number;
  name: string;
  cnpj: string;
};

export function UserCompanyAccessRequest() {
  const [mode, setMode] = useState<"search" | "create" | "view">("search");
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<Company[]>([]);
  const [loading, setLoading] = useState(false);
  const [requestingId, setRequestingId] = useState<number | null>(null);
  const navigate = useNavigate();
  const [selectedCompanyId, setSelectedCompanyId] = useState<number | null>(null);
  const user = useUser();

  // Função de busca
  const searchCompanies = useCallback(async (searchTerm: string) => {
    if (!searchTerm.trim()) {
      setResults([]);
      return;
    }

    try {
      setLoading(true);
      const res = await api.get("/companies", {
        params: { search: searchTerm }
      });
      setResults(res.data.data || []);
    } catch (err) {
      console.error("Erro ao buscar empresas:", err);
      setResults([]);
    } finally {
      setLoading(false);
    }
  }, []);

  // Debounce para buscar enquanto digita
  const debouncedSearch = useCallback(
    debounce((term: string) => {
      searchCompanies(term);
    }, 500),
    [searchCompanies]
  );

  // Executa busca quando query muda
  useEffect(() => {
    debouncedSearch(query);
    return () => {
      debouncedSearch.cancel();
    };
  }, [query, debouncedSearch]);

  // Solicitar vínculo
  const handleRequestAccess = async (companyId: number) => {
    try {
      setRequestingId(companyId);
      await api.post(`/companies/${companyId}/request-access`);
      alert("✅ Solicitação enviada com sucesso!");
    } catch (err) {
      console.error("Erro ao solicitar acesso:", err);
      alert("❌ Erro ao enviar solicitação. Tente novamente.");
    } finally {
      setRequestingId(null);
    }
  };

  // Criar empresa + solicitar vínculo
  const handleCreateCompany = async (formData: any) => {

    try {
      console.log('Enviando para API...');
      if (formData.img instanceof File) {
        const formDataToSend = new FormData();

        Object.entries(formData).forEach(([key, value]) => {
          if (value !== null && value !== undefined) {
            if (value instanceof File) {
              formDataToSend.append(key, value);
            } else {
              formDataToSend.append(key, value.toString());
            }
          }
        });

        await api.post("/companies/request-with-new-company", formDataToSend, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });
      } else {
        // Sem imagem, envia JSON normal
        const response = await api.post("/companies/request-with-new-company", formData);
      }

      alert("✅ Empresa criada e solicitação enviada!");
      window.history.back();
    } catch (error) {
      console.error('Erro ao cadastrar empresa:', error);
      console.error('Resposta do erro:', error.response);

      // Se for erro de validação, relança para o CompanyForm tratar
      if (error.response?.data?.errors) {
        throw error;
      } else {
        alert('Erro ao cadastrar empresa. Tente novamente.');
      }
    }
  };

  const formatCNPJ = (cnpj: string) => {
    return cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, "$1.$2.$3/$4-$5");
  };

  return (
    <div className="max-w-7xl mx-auto p-6">
      <CompanySelector
        companies={user.user.activeCompanies ?? []}
        selectedCompanyId={selectedCompanyId}
        onChange={setSelectedCompanyId}
      />
      <div className="bg-white rounded-lg shadow-sm border border-gray-200">
        {/* Header */}
        <div className="border-b border-gray-200 bg-gray-50 rounded-t-lg">
          <div className="px-6 py-4">
            <div className="flex items-center gap-3">
              <Button
                variant="ghost"
                size="sm"
                onClick={() => navigate("/")}
                className="h-8 w-8 sm:h-9 sm:w-9 p-0"
              >
                <ArrowLeft className="w-4 h-4" />
              </Button>
              <div className="p-2 bg-blue-100 rounded-lg">
                <Building2 className="w-5 h-5 text-blue-600" />
              </div>
              <div>
                <h2 className="text-xl font-semibold text-gray-900">
                  Solicitar acesso à empresa
                </h2>
                <p className="text-sm text-gray-500 mt-1">
                  Busque uma empresa existente ou cadastre uma nova
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Tabs */}
        <div className="border-b border-gray-200 px-6">
          <nav className="flex gap-8" aria-label="Tabs">
            <button
              onClick={() => {
                setMode("search");
                setQuery("");
                setResults([]);
              }}
              className={`
                py-3 px-1 font-medium text-sm transition-all duration-200 relative
                ${mode === "search"
                  ? "text-blue-600"
                  : "text-gray-500 hover:text-gray-700"
                }
              `}
            >
              <div className="flex items-center gap-2">
                <Search className="w-4 h-4" />
                Buscar empresa
              </div>
              {mode === "search" && (
                <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 rounded-full" />
              )}
            </button>
            <button
              onClick={() => {
                setMode("create");
                setQuery("");
                setResults([]);
              }}
              className={`
                py-3 px-1 font-medium text-sm transition-all duration-200 relative
                ${mode === "create"
                  ? "text-blue-600"
                  : "text-gray-500 hover:text-gray-700"
                }
              `}
            >
              <div className="flex items-center gap-2">
                <Building className="w-4 h-4" />
                Cadastrar nova empresa
              </div>
              {mode === "create" && (
                <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 rounded-full" />
              )}
            </button>

            <button
              onClick={() => {
                setMode("view");
                setQuery("");
                setResults([]);
              }}
              className={`
                py-3 px-1 font-medium text-sm transition-all duration-200 relative
                ${mode === "view"
                  ? "text-blue-600"
                  : "text-gray-500 hover:text-gray-700"
                }
              `}
            >
              <div className="flex items-center gap-2">
                <Building className="w-4 h-4" />
                Solicitações

                {user?.user?.pendingCompanies?.length > 0 && (
                  <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                    {user?.user?.pendingCompanies?.length > 9 ? '9+' : user?.user?.pendingCompanies?.length}
                  </span>
                )}
              </div>
              {mode === "view" && (
                <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 rounded-full" />
              )}
            </button>
          </nav>
        </div>

        {/* Conteúdo */}
        <div className="p-6">
          {/* 🔍 MODO BUSCA */}
          {mode === "search" && (
            <div className="space-y-6">
              {/* Campo de busca */}
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Search className="h-5 w-5 text-gray-400" />
                </div>
                <input
                  type="text"
                  placeholder="Buscar por nome ou CNPJ..."
                  value={query}
                  onChange={(e) => setQuery(e.target.value)}
                  className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  autoFocus
                />
              </div>

              {/* Resultados */}
              {loading && (
                <div className="flex items-center justify-center py-12">
                  <Loader2 className="w-6 h-6 text-blue-600 animate-spin" />
                  <span className="ml-2 text-gray-500">Buscando empresas...</span>
                </div>
              )}

              {!loading && query && results.length === 0 && (
                <div className="text-center py-12 bg-gray-50 rounded-lg border border-gray-200">
                  <AlertCircle className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                  <p className="text-gray-500">Nenhuma empresa encontrada</p>
                  <p className="text-sm text-gray-400 mt-1">
                    Tente outro nome ou CNPJ
                  </p>
                </div>
              )}

              {!loading && results.length > 0 && (
                <div className="space-y-3">
                  <p className="text-sm text-gray-500">
                    Encontradas {results.length} empresa{results.length !== 1 ? "s" : ""}
                  </p>
                  <div className="space-y-3">
                    {results.map((company) => (
                      <div
                        key={company.id}
                        className="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-all duration-200 bg-white"
                      >
                        <div className="flex items-start justify-between">
                          <div className="flex-1">
                            <div className="flex items-center gap-2 mb-1">
                              <Building className="w-4 h-4 text-gray-400" />
                              <h3 className="font-medium text-gray-900">
                                {company.name}
                              </h3>
                            </div>
                            <p className="text-sm text-gray-500 font-mono">
                              CNPJ: {formatCNPJ(company.cnpj)}
                            </p>
                          </div>
                          <button
                            onClick={() => handleRequestAccess(company.id)}
                            disabled={requestingId === company.id}
                            className={`
                              ml-4 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                              ${requestingId === company.id
                                ? "bg-gray-100 text-gray-400 cursor-not-allowed"
                                : "bg-blue-600 text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                              }
                            `}
                          >
                            {requestingId === company.id ? (
                              <div className="flex items-center gap-2">
                                <Loader2 className="w-4 h-4 animate-spin" />
                                Enviando...
                              </div>
                            ) : (
                              "Solicitar acesso"
                            )}
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {!loading && !query && (
                <div className="text-center py-12 bg-gray-50 rounded-lg border border-gray-200">
                  <Search className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                  <p className="text-gray-500">Digite o nome ou CNPJ para buscar</p>
                </div>
              )}
            </div>
          )}

          {/* 🏢 MODO CRIAÇÃO */}
          {mode === "create" && (
            <CompanyForm
              onSubmit={handleCreateCompany}
              showStatus={false}
              title="Cadastrar nova empresa"
              subtitle="Preencha os dados da empresa e solicite acesso"
              saveButtonText="Solicitar vínculo"
            />
          )}

          {mode === "view" && (
            <ManageCompanies endpoint="/user/company-requests"></ManageCompanies>
          )}
        </div>
      </div>
    </div>
  );
}