// src/components/admin/PageHeader.tsx
import { Button } from "@/components/ui/button";
import { ArrowLeft, Plus, Download, Upload } from "lucide-react";
import { useNavigate } from "react-router-dom";

interface PageHeaderProps {
    title: string;
    subtitle: string;
    showBackButton?: boolean;
    backUrl?: string;
    actions?: {
        label: string;
        icon?: React.ReactNode;
        onClick: () => void;
        variant?: "default" | "outline" | "destructive" | "ghost";
        show?: boolean;
    }[];
}

export function PageHeader({
    title,
    subtitle,
    showBackButton = true,
    backUrl = "/",
    actions = []
}: PageHeaderProps) {
    const navigate = useNavigate();

    return (
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div className="flex items-center gap-3">
                {showBackButton && (
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => navigate(backUrl)}
                        className="h-8 w-8 p-0 flex-shrink-0"
                    >
                        <ArrowLeft className="w-4 h-4" />
                    </Button>
                )}
                <div>
                    <h1 className="text-xl sm:text-3xl font-bold tracking-tight">{title}</h1>
                    <p className="text-xs sm:text-sm text-muted-foreground mt-0.5">{subtitle}</p>
                </div>
            </div>

            <div className="flex items-center gap-2">
                {actions.map((action, index) => (
                    action.show !== false && (
                        <Button
                            key={index}
                            variant={action.variant || "default"}
                            size="sm"
                            onClick={action.onClick}
                            className="flex-1 sm:flex-initial"
                        >
                            {action.icon}
                            <span className="hidden sm:inline ml-2">{action.label}</span>
                        </Button>
                    )
                ))}
            </div>
        </div>
    );
}