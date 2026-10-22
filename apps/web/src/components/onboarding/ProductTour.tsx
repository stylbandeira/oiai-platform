import { useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { Button } from "@/components/ui/button";
import { X } from "lucide-react";

interface TourStep {
  target: string;
  title: string;
  description: string;
  allowAction?: boolean;
  advanceOnClick?: boolean;
}

interface ProductTourProps {
  steps: TourStep[];
  storageKey: string;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
  onStepChange?: (step: number) => void;
}

export function ProductTour({ steps, storageKey, open, onOpenChange, onStepChange }: ProductTourProps) {
  const [step, setStep] = useState(0);
  const [isOpen, setIsOpen] = useState(false);
  const [rect, setRect] = useState<DOMRect | null>(null);
  const goTo = (next: number) => {
    setStep(next);
    onStepChange?.(next);
  };
  const close = () => {
    localStorage.setItem(storageKey, "completed");
    setIsOpen(false);
    setStep(0);
    onOpenChange?.(false);
  };

  useEffect(() => {
    setIsOpen(open ?? !localStorage.getItem(storageKey));
    if (open) setStep(0);
  }, [open, storageKey]);

  useEffect(() => {
    if (!isOpen || !steps[step]) return;
    const findTarget = () => Array.from(document.querySelectorAll<HTMLElement>(steps[step].target))
      .find((element) => element.getClientRects().length > 0);
    const update = () => setRect(findTarget()?.getBoundingClientRect() ?? null);
    findTarget()?.scrollIntoView({ behavior: "smooth", block: "center" });
    update();
    const interval = window.setInterval(update, 150);
    window.addEventListener("resize", update);
    window.addEventListener("scroll", update, true);
    const handleClick = (event: MouseEvent) => {
      const target = findTarget();
      if (!target || !target.contains(event.target as Node)) return;
      if (!steps[step].allowAction) {
        event.preventDefault();
        event.stopImmediatePropagation();
      }
      if (steps[step].advanceOnClick === false) return;
      if (step === steps.length - 1) close();
      else goTo(step + 1);
    };
    document.addEventListener("click", handleClick, true);
    return () => {
      document.removeEventListener("click", handleClick, true);
      window.clearInterval(interval);
      window.removeEventListener("resize", update);
      window.removeEventListener("scroll", update, true);
    };
  }, [isOpen, step, steps]);

  useEffect(() => {
    if (!isOpen) return;
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") close();
      if (event.key === "ArrowRight") goTo(Math.min(step + 1, steps.length - 1));
      if (event.key === "ArrowLeft") goTo(Math.max(step - 1, 0));
    };
    document.addEventListener("keydown", onKeyDown);
    return () => document.removeEventListener("keydown", onKeyDown);
  }, [isOpen, step, steps.length]);

  if (!isOpen || !steps[step]) return null;
  const left = rect ? Math.max(0, rect.left - 6) : 0;
  const top = rect ? Math.max(0, rect.top - 6) : 0;
  const right = rect ? Math.min(window.innerWidth, rect.right + 6) : 0;
  const bottom = rect ? Math.min(window.innerHeight, rect.bottom + 6) : 0;
  const shade = "fixed z-[100] bg-slate-950/50";

  return createPortal(
    <>
      {rect ? (
        <>
          <div className={shade} style={{ top: 0, left: 0, right: 0, height: top }} />
          <div className={shade} style={{ top, left: 0, width: left, height: bottom - top }} />
          <div className={shade} style={{ top, left: right, right: 0, height: bottom - top }} />
          <div className={shade} style={{ top: bottom, left: 0, right: 0, bottom: 0 }} />
          <div className="pointer-events-none fixed z-[101] rounded-lg ring-4 ring-primary" style={{ top, left, width: right - left, height: bottom - top }} />
        </>
      ) : <div className={`${shade} inset-0`} />}
      <section role="dialog" aria-labelledby="tour-title" className="pointer-events-auto fixed inset-x-4 bottom-4 z-[200] max-h-[min(80vh,420px)] overflow-y-auto rounded-2xl border bg-card p-5 text-card-foreground shadow-medium sm:bottom-8 sm:left-auto sm:right-8 sm:w-full sm:max-w-sm">
        <div className="flex items-start justify-between gap-4">
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-primary">Passo {step + 1} de {steps.length}</p>
            <h2 id="tour-title" className="mt-1 text-lg font-bold">{steps[step].title}</h2>
          </div>
          <Button variant="ghost" size="icon" onClick={close} aria-label="Fechar tutorial"><X /></Button>
        </div>
        <p className="mt-2 text-sm leading-6 text-muted-foreground">{steps[step].description}</p>
        <p className="mt-2 text-xs font-medium text-primary">Clique no elemento destacado ou use “Avançar”.</p>
        <div className="mt-5 flex items-center justify-between gap-2">
          <Button variant="link" className="px-0" onClick={close}>Pular tutorial</Button>
          <div className="flex gap-2">
            <Button variant="outline" onClick={() => goTo(step - 1)} disabled={step === 0}>Voltar</Button>
            <Button onClick={() => step === steps.length - 1 ? close() : goTo(step + 1)}>
              {step === steps.length - 1 ? "Concluir" : "Avançar"}
            </Button>
          </div>
        </div>
      </section>
    </>, document.body
  );
}
