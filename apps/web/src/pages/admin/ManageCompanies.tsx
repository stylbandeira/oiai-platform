import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Building2, Search, Edit3, Trash2, Plus, ArrowLeft, Globe, MapPin } from "lucide-react";
import { useNavigate } from "react-router-dom";
import api from "@/lib/api";
import { CustomPagination } from "@/components/oiai_ui/CustomPagination";
import { useUser } from "@/contexts/UserContext";
import type { Company } from "@/types/company";


interface Props {
    endpoint: string;
}

export default function ManageCompanies({ endpoint }: Props) {
    const navigate = useNavigate();
    const [companies, setCompanies] = useState<Company[]>([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState("");
    const [filterStatus, setFilterStatus] = useState<string>("all");
    const [paginationMeta, setPaginationMeta] = useState<any>(null);
    const [searchTimeout, setSearchTimeout] = useState<NodeJS.Timeout | null>(null);
    const user = useUser();

    useEffect(() => {
        fetchCompanies();
    }, []);

    useEffect(() => {
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        const timeout = setTimeout(() => {
            fetchCompanies(1, search, filterStatus);
        }, 500);

        setSearchTimeout(timeout);

        return () => {
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
        };
    }, [search, filterStatus]);

    useEffect(() => {
        fetchCompanies(1, search, filterStatus);
    }, [filterStatus]);

    const fetchCompanies = async (page: number = 1, searchTerm: string = search, status: string = filterStatus) => {
        try {
            setLoading(true);
            const params: any = { page };

            if (searchTerm) params.search = searchTerm;
            if (status !== "all") params.status = status;

            const response = await api.get(endpoint, { params });

            setCompanies(response.data.data);
            setPaginationMeta(response.data.meta);
        } catch (error) {
            console.error('Erro ao carregar empresas:', error);
        } finally {
            setLoading(false);
        }
    };

    const handlePageChange = (page: number, searchTerm: string, status: string) => {
        fetchCompanies(page, searchTerm, status);
        window.scrollTo(0, 0);
    };

    const handleDelete = async (companyId: number) => {
        try {
            const response = await api.delete(`admin/companies/${companyId}`);
            fetchCompanies();
        } catch (error) {
            console.error('Erro ao deletar empresa', error);
        }
    }

    const normalizedSearch = search.toLowerCase();
    const filteredCompanies = companies.filter(company => {
        const matchesSearch = (company.name ?? '').toLowerCase().includes(normalizedSearch) ||
            (company.email ?? '').toLowerCase().includes(normalizedSearch) ||
            (company.cnpj ?? '').includes(search);
        const matchesStatus = filterStatus === "all" || company.status === filterStatus;

        return matchesSearch && matchesStatus;
    });

    const getStatusBadge = (status: string) => {
        const variants = {
            'active': 'default',
            'inactive': 'secondary',
            'pending': 'outline'
        };
        const labels = {
            'active': 'Ativo',
            'inactive': 'Inativo',
            'pending': 'Pendente'
        };

        return (
            <Badge variant={variants[status as keyof typeof variants] as any}>
                {labels[status as keyof typeof labels]}
            </Badge>
        );
    };

    return (
        <>
            <div className="container mx-auto px-4 py-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    {user.user.type === 'admin' && (
                        <>
                            <div className="flex items-center gap-4">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => navigate('/')}
                                >
                                    <ArrowLeft className="w-5 h-5" />
                                </Button>
                                <div>
                                    <h1 className="text-3xl font-bold">Gerenciar Empresas</h1>
                                    <p className="text-muted-foreground">
                                        Gerencie todas as empresas cadastradas na plataforma
                                    </p>
                                </div>
                            </div>
                            <Button
                                onClick={() => navigate('/admin/companies/new')}
                            >
                                <Plus className="w-4 h-4 mr-2" />
                                Nova Empresa
                            </Button>
                        </>
                    )}
                </div>

                {/* Filtros */}
                {user.user.type === 'admin' && (
                    <>
                        <Card className="border-0 shadow-soft">
                            <CardContent className="p-6">
                                <div className="flex flex-col sm:flex-row gap-4">
                                    <div className="relative flex-1">
                                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                        <Input
                                            placeholder="Buscar por nome, email ou CNPJ..."
                                            className="pl-10"
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                        />
                                    </div>
                                    <Select value={filterStatus} onValueChange={setFilterStatus}>
                                        <SelectTrigger className="w-full sm:w-48">
                                            <SelectValue placeholder="Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todos os status</SelectItem>
                                            <SelectItem value="active">Ativo</SelectItem>
                                            <SelectItem value="inactive">Inativo</SelectItem>
                                            <SelectItem value="pending">Pendente</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </CardContent>
                        </Card>
                    </>
                )}

                {/* Tabela de Empresas */}
                <Card className="border-0 shadow-soft">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Building2 className="w-5 h-5" />
                            Empresas ({filteredCompanies.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Empresa</TableHead>
                                    <TableHead>CNPJ</TableHead>
                                    {user.user.type === 'company' && (
                                        <>
                                            <TableHead>Solicitação</TableHead>
                                        </>
                                    )}
                                    {user.user.type === 'admin' && (
                                        <>
                                            <TableHead>Produtos</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Cadastro</TableHead>
                                            <TableHead className="text-right">Ações</TableHead>
                                        </>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filteredCompanies.map((company) => (
                                    <TableRow key={company.id}>
                                        <TableCell>
                                            <div>
                                                <p className="font-medium">{company.name}</p>
                                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                    <span>{company.email}</span>
                                                    {company.website && (
                                                        <>
                                                            <span>•</span>
                                                            <Globe className="w-3 h-3" />
                                                            <span>Site</span>
                                                        </>
                                                    )}
                                                </div>
                                                <div className="flex items-start gap-1 text-xs text-muted-foreground mt-1">
                                                    <MapPin className="w-3 h-3 mt-0.5 flex-shrink-0" />
                                                    <span>{company.full_address}</span>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <code className="text-xs bg-muted px-2 py-1 rounded">
                                                {company.cnpj}
                                            </code>
                                        </TableCell>
                                        {user.user.type === 'company' && (
                                            <>
                                                <TableCell>{getStatusBadge(company.ownership_status)}</TableCell>
                                            </>
                                        )}

                                        {/* AÇÕES */}
                                        {user.user.type === 'admin' && (
                                            <>
                                                <TableCell>
                                                    <span className="font-medium">{company.total_products.toLocaleString()}</span>
                                                </TableCell>
                                                <TableCell>{getStatusBadge(company.status)}</TableCell>
                                                <TableCell>
                                                    {new Date(company.created_at).toLocaleDateString('pt-BR')}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Button variant="ghost" size="icon" onClick={() => navigate(`/admin/companies/edit/${company.id}`)}>
                                                            <Edit3 className="w-4 h-4" />
                                                        </Button>
                                                        <Button variant="ghost" size="icon" onClick={() => handleDelete(company.id)}>
                                                            <Trash2 className="w-4 h-4" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </>
                                        )}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        <CustomPagination
                            paginationMeta={paginationMeta}
                            search={search}
                            filterStatus={filterStatus}
                            onPageChange={handlePageChange}
                        >

                        </CustomPagination>
                    </CardContent>
                </Card>
            </div>
        </ >
    );
}
