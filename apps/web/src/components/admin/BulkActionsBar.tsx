// src/components/admin/BulkActionsBar.tsx
import { Button } from "@/components/ui/button";
import { CheckCheck, X, CheckCircle, XCircle, ArchiveRestore, Trash2 } from "lucide-react";

interface BulkAction {
    label: string;
    icon?: React.ReactNode;
    onClick: () => void;
    variant?: "default" | "destructive" | "outline" | "ghost";
    className?: string;
}

interface BulkActionsBarProps {
    selectedCount: number;
    actions: BulkAction[];
    onClear: () => void;
}

export function BulkActionsBar({ selectedCount, actions, onClear }: BulkActionsBarProps) {
    if (selectedCount === 0) return null;

    return (
        <div className="bg-primary/5 border border-primary/20 rounded-lg p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div className="flex items-center gap-2">
                <CheckCheck className="w-4 h-4 sm:w-5 sm:h-5 text-primary" />
                <span className="text-sm sm:text-base font-medium">
                    {selectedCount} item{selectedCount > 1 ? 's' : ''} selecionado{selectedCount > 1 ? 's' : ''}
                </span>
            </div>

            <div className="flex flex-wrap items-center gap-2">
                {actions.map((action, index) => (
                    <Button
                        key={index}
                        variant={action.variant || "outline"}
                        size="sm"
                        onClick={action.onClick}
                        className={action.className}
                    >
                        {action.icon}
                        <span className="hidden sm:inline ml-2">{action.label}</span>
                    </Button>
                ))}
                <Button variant="ghost" size="sm" onClick={onClear}>
                    <X className="w-4 h-4 sm:mr-2" />
                    <span className="hidden sm:inline">Limpar</span>
                </Button>
            </div>
        </div>
    );
}