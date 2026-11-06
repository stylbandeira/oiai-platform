import { useCallback, useEffect, useMemo, useState } from "react";
import type { FormEvent, ReactNode } from "react";
import { Activity, AlertTriangle, ArrowLeft, CheckCircle2, Clock3, Loader2, RefreshCw, Timer } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import api from "@/lib/api";
import { getApiErrorMessage } from "@/utils/apiError";
import { useNavigate } from "react-router-dom";

interface BurasSummary {
  provider: string;
  operation: string;
  calls: number;
  successes: number;
  failures: number;
  timeouts: number;
  average_ms: number;
  p95_ms: number;
}

interface BurasEvent {
  event_id: string;
  event: string;
  occurred_at: string;
  environment: string;
  provider: string;
  operation: string;
  result: string;
  http_status: number | null;
  duration_ms: number | null;
  correlation_id: string;
}

interface BurasCheck {
  id: number;
  layer: string;
  name: string;
  provider: string;
  operation: string;
  status: string;
  detail_code: string;
  duration_ms: number;
  occurred_at: string;
}

interface BurasAlert {
  key: string;
  environment: string;
  provider: string;
  operation: string;
  state: string;
  last_seen: string;
}

interface BurasOverview {
  summary: BurasSummary[];
  events: BurasEvent[];
  checks: BurasCheck[];
  alerts: BurasAlert[];
}

const defaultEnvironment = import.meta.env.PROD ? "production" : "local";

const periodOptions = [
  { value: "1h", label: "Última hora" },
  { value: "6h", label: "Últimas 6 horas" },
  { value: "24h", label: "Últimas 24 horas" },
  { value: "72h", label: "Últimos 3 dias" },
  { value: "168h", label: "Últimos 7 dias" },
  { value: "720h", label: "Últimos 30 dias" },
];

function formatDate(value: string): string {
  const date = new Date(value);

  return Number.isNaN(date.getTime()) ? value : date.toLocaleString("pt-BR");
}

function stateBadge(state: string) {
  return state === "success" || state === "resolved" || state === "pass"
    ? "completed"
    : state === "firing" || state === "failure" || state === "timeout"
      ? "destructive"
      : "secondary";
}

export default function BurasDashboard() {
  const navigate = useNavigate();
  const [environment, setEnvironment] = useState(defaultEnvironment);
  const [provider, setProvider] = useState("");
  const [period, setPeriod] = useState("24h");
  const [appliedFilters, setAppliedFilters] = useState({ environment: defaultEnvironment, provider: "", period: "24h" });
  const [overview, setOverview] = useState<BurasOverview | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const loadOverview = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const response = await api.get<BurasOverview>("/admin/buras/overview", {
        params: {
          ...appliedFilters,
          provider: appliedFilters.provider || undefined,
        },
      });
      setOverview(response.data);
    } catch (requestError: unknown) {
      setOverview(null);
      setError(getApiErrorMessage(requestError, "Não foi possível carregar o painel do Buras."));
    } finally {
      setLoading(false);
    }
  }, [appliedFilters]);

  useEffect(() => {
    void loadOverview();
  }, [loadOverview]);

  const totals = useMemo(() => {
    const rows = overview?.summary ?? [];
    const calls = rows.reduce((total, row) => total + row.calls, 0);
    const successes = rows.reduce((total, row) => total + row.successes, 0);
    const failures = rows.reduce((total, row) => total + row.failures, 0);
    const timeouts = rows.reduce((total, row) => total + row.timeouts, 0);
    const p95 = rows.reduce((maximum, row) => Math.max(maximum, row.p95_ms), 0);

    return {
      calls,
      successRate: calls > 0 ? Math.round((successes / calls) * 100) : 0,
      failures,
      timeouts,
      p95,
    };
  }, [overview]);

  const recentEvents = useMemo(() => {
    const cutoff = Date.now() - Number.parseInt(appliedFilters.period, 10) * 60 * 60 * 1000;

    return (overview?.events ?? []).filter((event) => Date.parse(event.occurred_at) >= cutoff);
  }, [appliedFilters.period, overview]);

  const recentChecks = useMemo(() => {
    const cutoff = Date.now() - Number.parseInt(appliedFilters.period, 10) * 60 * 60 * 1000;

    return (overview?.checks ?? []).filter((check) => Date.parse(check.occurred_at) >= cutoff);
  }, [appliedFilters.period, overview]);

  const applyFilters = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setAppliedFilters({ environment, provider: provider.trim(), period });
  };

  return (
    <div className="container mx-auto space-y-6 px-4 py-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div className="flex items-start gap-3">
          <Button
            variant="ghost"
            size="icon"
            aria-label="Voltar à página anterior"
            onClick={() => {
              if (window.history.state?.idx > 0) {
                navigate(-1);
              } else {
                navigate("/");
              }
            }}
          >
            <ArrowLeft className="h-4 w-4" />
          </Button>
          <div>
            <div className="flex items-center gap-2 text-primary">
              <Activity className="h-5 w-5" />
              <span className="text-sm font-semibold">Buras</span>
            </div>
            <h1 className="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Observabilidade</h1>
            <p className="mt-1 text-sm text-muted-foreground">
              Saúde, latência, eventos e alertas das integrações externas.
            </p>
          </div>
        </div>
        <Button variant="outline" onClick={() => void loadOverview()} disabled={loading}>
          {loading ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <RefreshCw className="mr-2 h-4 w-4" />}
          Atualizar
        </Button>
      </div>

      <Card>
        <CardContent className="p-4">
          <form onSubmit={applyFilters} className="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end">
            <label className="space-y-1 text-sm font-medium">
              Ambiente
              <select
                value={environment}
                onChange={(event) => setEnvironment(event.target.value)}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              >
                <option value="local">Local</option>
                <option value="staging">Homologação</option>
                <option value="production">Produção</option>
              </select>
            </label>
            <label className="space-y-1 text-sm font-medium">
              Provedor
              <input
                value={provider}
                onChange={(event) => setProvider(event.target.value)}
                placeholder="Todos os provedores"
                maxLength={80}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
            </label>
            <label className="space-y-1 text-sm font-medium">
              Período
              <select
                value={period}
                onChange={(event) => setPeriod(event.target.value)}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              >
                {periodOptions.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
              </select>
            </label>
            <Button type="submit" disabled={loading}>Aplicar filtros</Button>
          </form>
        </CardContent>
      </Card>

      {error && (
        <Card className="border-destructive/40 bg-destructive/5">
          <CardContent className="flex items-start gap-3 p-4 text-sm">
            <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-destructive" />
            <div>
              <p className="font-semibold">Não foi possível consultar o Buras</p>
              <p className="mt-1 text-muted-foreground">{error}</p>
              <p className="mt-2 text-muted-foreground">Confira se o serviço está ativo e configurado para este ambiente.</p>
            </div>
          </CardContent>
        </Card>
      )}

      {loading && !overview ? (
        <div className="flex min-h-52 items-center justify-center text-muted-foreground">
          <Loader2 className="mr-2 h-5 w-5 animate-spin" /> Carregando observabilidade…
        </div>
      ) : overview && (
        <>
          <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard title="Chamadas" value={totals.calls.toLocaleString("pt-BR")} detail={`${totals.successRate}% de sucesso`} icon={<Activity className="h-5 w-5" />} />
            <MetricCard title="Falhas" value={totals.failures.toLocaleString("pt-BR")} detail="No período selecionado" icon={<AlertTriangle className="h-5 w-5" />} tone={totals.failures > 0 ? "danger" : "default"} />
            <MetricCard title="Timeouts" value={totals.timeouts.toLocaleString("pt-BR")} detail="Timeout ou conexão indisponível" icon={<Clock3 className="h-5 w-5" />} tone={totals.timeouts > 0 ? "warning" : "default"} />
            <MetricCard title="Maior p95" value={`${totals.p95.toLocaleString("pt-BR", { maximumFractionDigits: 0 })} ms`} detail="Entre as operações listadas" icon={<Timer className="h-5 w-5" />} />
          </div>

          <Card>
            <CardHeader>
              <CardTitle>Saúde das integrações</CardTitle>
              <CardDescription>Volume, taxa de sucesso e latência por provedor e operação.</CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Provedor</TableHead><TableHead>Operação</TableHead><TableHead>Chamadas</TableHead>
                    <TableHead>Sucesso</TableHead><TableHead>Falhas</TableHead><TableHead>Timeouts</TableHead>
                    <TableHead>Média</TableHead><TableHead>p95</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {overview.summary.map((row) => (
                    <TableRow key={`${row.provider}:${row.operation}`}>
                      <TableCell className="font-medium">{row.provider}</TableCell>
                      <TableCell>{row.operation}</TableCell>
                      <TableCell>{row.calls.toLocaleString("pt-BR")}</TableCell>
                      <TableCell>{row.calls > 0 ? `${Math.round((row.successes / row.calls) * 100)}%` : "—"}</TableCell>
                      <TableCell>{row.failures}</TableCell>
                      <TableCell>{row.timeouts}</TableCell>
                      <TableCell>{row.average_ms.toLocaleString("pt-BR", { maximumFractionDigits: 1 })} ms</TableCell>
                      <TableCell>{row.p95_ms.toLocaleString("pt-BR", { maximumFractionDigits: 1 })} ms</TableCell>
                    </TableRow>
                  ))}
                  {overview.summary.length === 0 && <EmptyRow columns={8} message="Nenhuma chamada no período e filtros escolhidos." />}
                </TableBody>
              </Table>
            </CardContent>
          </Card>

          <div className="grid gap-6 xl:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>Alertas</CardTitle>
                <CardDescription>Incidentes ativos e resolvidos para o ambiente selecionado.</CardDescription>
              </CardHeader>
              <CardContent>
                {overview.alerts.length === 0 ? <EmptyMessage message="Nenhum alerta registrado." /> : (
                  <div className="space-y-3">
                    {overview.alerts.map((alert) => (
                      <div key={alert.key} className="flex flex-col gap-2 rounded-lg border p-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                          <p className="font-medium">{alert.provider} <span className="text-muted-foreground">/ {alert.operation}</span></p>
                          <p className="text-xs text-muted-foreground">Última ocorrência: {formatDate(alert.last_seen)}</p>
                        </div>
                        <Badge variant={stateBadge(alert.state)}>{alert.state === "firing" ? "Ativo" : "Resolvido"}</Badge>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Camadas de checagem</CardTitle>
                <CardDescription>Resultados das verificações passivas e sondas ativas.</CardDescription>
              </CardHeader>
              <CardContent>
                {recentChecks.length === 0 ? <EmptyMessage message="Nenhuma checagem registrada no período." /> : (
                  <div className="space-y-3">
                    {recentChecks.map((check) => (
                      <div key={check.id} className="flex flex-col gap-2 rounded-lg border p-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                          <p className="font-medium">{check.name} <span className="text-muted-foreground">/ {check.provider}</span></p>
                          <p className="text-xs text-muted-foreground">{check.layer} · {check.operation} · {formatDate(check.occurred_at)}</p>
                        </div>
                        <Badge variant={stateBadge(check.status)}>{check.status}</Badge>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </div>

          <Card>
            <CardHeader>
              <CardTitle>Eventos recentes</CardTitle>
              <CardDescription>Eventos sanitizados recebidos pelo Buras. Correlação disponível para investigação.</CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Quando</TableHead><TableHead>Integração</TableHead><TableHead>Resultado</TableHead>
                    <TableHead>HTTP</TableHead><TableHead>Duração</TableHead><TableHead>Correlação</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {recentEvents.map((event) => (
                    <TableRow key={event.event_id}>
                      <TableCell className="whitespace-nowrap">{formatDate(event.occurred_at)}</TableCell>
                      <TableCell><span className="font-medium">{event.provider}</span><span className="block text-xs text-muted-foreground">{event.operation}</span></TableCell>
                      <TableCell><Badge variant={stateBadge(event.result)}>{event.result}</Badge></TableCell>
                      <TableCell>{event.http_status ?? "—"}</TableCell>
                      <TableCell>{event.duration_ms === null ? "—" : `${event.duration_ms} ms`}</TableCell>
                      <TableCell className="max-w-40 break-all font-mono text-xs">{event.correlation_id}</TableCell>
                    </TableRow>
                  ))}
                  {recentEvents.length === 0 && <EmptyRow columns={6} message="Nenhum evento recebido pelos filtros atuais no período." />}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </>
      )}
    </div>
  );
}

function MetricCard({
  title,
  value,
  detail,
  icon,
  tone = "default",
}: {
  title: string;
  value: string;
  detail: string;
  icon: ReactNode;
  tone?: "default" | "danger" | "warning";
}) {
  const color = tone === "danger" ? "text-destructive bg-destructive/10" : tone === "warning" ? "text-amber-700 bg-amber-100" : "text-primary bg-primary/10";

  return (
    <Card>
      <CardContent className="flex items-center gap-3 p-4 sm:p-5">
        <div className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-lg ${color}`}>{icon}</div>
        <div className="min-w-0">
          <p className="text-sm text-muted-foreground">{title}</p>
          <p className="text-2xl font-bold leading-tight">{value}</p>
          <p className="truncate text-xs text-muted-foreground">{detail}</p>
        </div>
      </CardContent>
    </Card>
  );
}

function EmptyRow({ columns, message }: { columns: number; message: string }) {
  return <TableRow><TableCell colSpan={columns} className="py-8 text-center text-muted-foreground">{message}</TableCell></TableRow>;
}

function EmptyMessage({ message }: { message: string }) {
  return (
    <div className="flex items-center gap-2 py-5 text-sm text-muted-foreground">
      <CheckCircle2 className="h-4 w-4" />{message}
    </div>
  );
}
