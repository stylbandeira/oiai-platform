import { useEffect, useMemo, useState } from "react";
import api from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { getApiErrorMessage } from "@/utils/apiError";

export type ProductDecisionAttribute = "name" | "quantity" | "quantity_dimension";

interface Unity { id: number; name: string; abbreviation: string; }

interface ProductDecisionPopupProps {
  open: boolean;
  productId: number;
  rawName: string;
  attribute: ProductDecisionAttribute;
  initialValue?: string | null;
  canSkip?: boolean;
  onComplete: (product?: {
    id: number;
    name: string;
    normalized_quantity?: string | null;
    quantity?: number | null;
    unity_quantity?: number | null;
    unity?: string | null;
    quantity_dimension?: string | null;
    unit_id?: number | null;
    unity_id?: number | null;
    normalization_validated?: boolean;
    normalization_next_attribute?: ProductDecisionAttribute | null;
    name_normalization_validated?: boolean;
    quantity_normalization_validated?: boolean;
  }) => void;
}

function similarity(a: string, b: string): number {
  const left = a.trim().toLowerCase();
  const right = b.trim().toLowerCase();
  if (!left || !right) return 0;
  const distance = levenshtein(left, right);
  return 1 - distance / Math.max(left.length, right.length);
}

function levenshtein(a: string, b: string): number {
  const row = Array.from({ length: b.length + 1 }, (_, index) => index);
  for (let i = 1; i <= a.length; i += 1) {
    let previous = row[0];
    row[0] = i;
    for (let j = 1; j <= b.length; j += 1) {
      const current = row[j];
      row[j] = a[i - 1] === b[j - 1]
        ? previous
        : Math.min(previous + 1, row[j - 1] + 1, current + 1);
      previous = current;
    }
  }
  return row[b.length];
}

export function ProductDecisionPopup({
  open, productId, rawName, attribute, initialValue = "", canSkip = false, onComplete,
}: ProductDecisionPopupProps) {
  const [value, setValue] = useState(initialValue ?? "");
  const [unities, setUnities] = useState<Unity[]>([]);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [dimension, setDimension] = useState("");
  const [quantityChoice, setQuantityChoice] = useState("");
  const [dimensionChoice, setDimensionChoice] = useState("");
  const numericDecision = attribute !== "name";

  useEffect(() => {
    setValue(initialValue ?? "");
  }, [initialValue, attribute]);

  useEffect(() => {
    if (attribute !== "quantity" && attribute !== "quantity_dimension") return;
    api.get("/unities").then(({ data }) => {
      setUnities(data.unities ?? data.data ?? data ?? []);
    }).catch(() => setUnities([]));
  }, [attribute]);

  const matchedUnity = useMemo(() => {
    const suffix = value.match(/(?:^|\s)([a-z]{1,2})$/i)?.[1]?.toLowerCase();
    return unities.find((unity) => unity.abbreviation.toLowerCase() === suffix);
  }, [unities, value]);

  const quantityOptions = useMemo(() => {
    const numbers = Array.from(rawName.matchAll(/\d+(?:[,.]\d+)?/g)).map((match) => match[0]);
    const options = [numbers[0], numbers[1], "100"].filter(Boolean);

    return Array.from(new Set(options)).slice(0, 3);
  }, [rawName]);

  const dimensionOptions = useMemo(() => {
    const suffixes = Array.from(rawName.matchAll(/\d+(?:[,.]\d+)?\s*([a-z]{1,3})/gi))
      .map((match) => match[1].toLowerCase());
    const ranked = unities
      .map((unity) => ({ unity, score: Math.max(
        ...suffixes.map((suffix) => similarity(suffix, unity.abbreviation)),
        ...suffixes.map((suffix) => similarity(suffix, unity.name)),
        0,
      ) }))
      .sort((left, right) => right.score - left.score)
      .map(({ unity }) => unity.abbreviation);

    // Preserve the unit explicitly written in the product name as the first
    // suggestion, even when the units endpoint is paginated or outdated.
    const explicitUnits = suffixes.filter((suffix) => suffix.length >= 1);
    const fallback = ["un", "g", "ml"];
    return Array.from(new Set([...explicitUnits, ...ranked, ...fallback])).slice(0, 3);
  }, [rawName, unities]);

  const validName = attribute !== "name" || similarity(rawName, value) >= 0.35;

  const submit = async () => {
    setSaving(true);
    setError(null);
    try {
      const selectedValues = !numericDecision
        ? { normalized_name: value }
        : {
          normalized_quantity: `${quantityChoice === "custom" ? value : quantityChoice} ${dimensionChoice === "custom" ? dimension : dimensionChoice}`.trim(),
          quantity_dimension: dimensionChoice === "custom" ? dimension : dimensionChoice,
        };
      const response = await api.post(`/products/${productId}/normalization-decisions`, {
        selected_values: selectedValues,
        algorithm_version: 2,
      });
      onComplete(response.data.product);
    } catch (requestError: unknown) {
      setError(getApiErrorMessage(requestError, "Não foi possível salvar a decisão."));
    } finally {
      setSaving(false);
    }
  };

  const title = numericDecision ? "Confirmar quantidade e unidade" : "Confirmar nome do produto";

  return <Dialog open={open} onOpenChange={(next) => !next && canSkip && onComplete()}>
    <DialogContent>
      <DialogHeader><DialogTitle>{title}</DialogTitle></DialogHeader>
      <p className="text-sm text-muted-foreground">Valor original: <strong>{rawName}</strong></p>
      <div className="rounded-md border-2 border-primary bg-primary/5 p-4 space-y-3">
        <p className="text-sm font-semibold">{numericDecision ? "Quantidade e unidade" : "Nome normalizado"}</p>
        {!numericDecision && <>
          <Label htmlFor="decision-value">Nome definido pelo usuário</Label>
          <Input id="decision-value" value={value} onChange={(event) => setValue(event.target.value)} />
        </>}
        {numericDecision && <>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Quantidade</Label>
              {quantityOptions.map((option) => <label key={option} className="flex gap-2 items-center text-sm">
                <input type="radio" name="quantity-choice" checked={quantityChoice === option} onChange={() => { setQuantityChoice(option); setValue(`${option} ${dimensionChoice}`.trim()); }} />
                {option}
              </label>)}
              <label className="flex gap-2 items-center text-sm"><input type="radio" name="quantity-choice" checked={quantityChoice === "custom"} onChange={() => setQuantityChoice("custom")} /> Outro</label>
              {quantityChoice === "custom" && <Input value={value} onChange={(event) => setValue(event.target.value)} placeholder="Ex.: 400 g" />}
            </div>
            <div className="space-y-2">
              <Label>Unidade</Label>
              {dimensionOptions.map((option) => <label key={option} className="flex gap-2 items-center text-sm">
                <input type="radio" name="dimension-choice" checked={dimensionChoice === option} onChange={() => { setDimensionChoice(option); setDimension(option); }} />
                {option}
              </label>)}
              <label className="flex gap-2 items-center text-sm"><input type="radio" name="dimension-choice" checked={dimensionChoice === "custom"} onChange={() => setDimensionChoice("custom")} /> Outro</label>
              {dimensionChoice === "custom" && <Input value={dimension} onChange={(event) => setDimension(event.target.value)} placeholder="Ex.: massa" />}
            </div>
          </div>
          {matchedUnity && <p className="text-xs text-muted-foreground">Unidade reconhecida: {matchedUnity.name}</p>}
        </>}
      </div>
      {error && <p className="text-sm text-destructive">{error}</p>}
      {!numericDecision && !validName && <p className="text-xs text-amber-600">O backend aceitará o valor, mas poderá classificá-lo como não validado pela baixa proximidade.</p>}
      <div className="flex justify-end gap-2">
        {canSkip && <Button variant="outline" onClick={() => onComplete()}>Agora não</Button>}
        <Button disabled={saving || (!numericDecision && !value.trim()) || (numericDecision && !(quantityChoice && dimensionChoice))} onClick={() => submit()}>
          Confirmar
        </Button>
      </div>
    </DialogContent>
  </Dialog>;
}
