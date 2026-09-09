// src/components/admin/ResponsiveTable.tsx
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Card, CardContent } from "@/components/ui/card";
import { Loader2 } from "lucide-react";
import { ReactNode } from "react";

interface Column {
    key: string;
    header: string;
    className?: string;
    mobileHidden?: boolean;
    render?: (item: any) => ReactNode;
}

interface ResponsiveTableProps {
    columns: Column[];
    data: any[];
    loading?: boolean;
    emptyMessage?: string;
    renderMobileCard: (item: any) => ReactNode;
    onRowClick?: (item: any) => void;
}

export function ResponsiveTable({
    columns,
    data,
    loading = false,
    emptyMessage = "Nenhum item encontrado",
    renderMobileCard,
    onRowClick
}: ResponsiveTableProps) {

    if (loading) {
        return (
            <div className="flex items-center justify-center py-12">
                <Loader2 className="w-8 h-8 animate-spin text-primary" />
            </div>
        );
    }

    if (data.length === 0) {
        return (
            <div className="text-center py-12 text-muted-foreground">
                <p>{emptyMessage}</p>
            </div>
        );
    }

    return (
        <>
            {/* Desktop Table */}
            <div className="hidden md:block overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            {columns.map((column) => (
                                <TableHead key={column.key} className={column.className}>
                                    {column.header}
                                </TableHead>
                            ))}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {data.map((item, index) => (
                            <TableRow
                                key={index}
                                className={onRowClick ? "cursor-pointer hover:bg-muted/50" : ""}
                                onClick={() => onRowClick?.(item)}
                            >
                                {columns.map((column) => (
                                    <TableCell key={column.key} className={column.className}>
                                        {column.render ? column.render(item) : item[column.key]}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            {/* Mobile Cards */}
            <div className="md:hidden space-y-3">
                {data.map((item, index) => (
                    <div key={index} onClick={() => onRowClick?.(item)}>
                        {renderMobileCard(item)}
                    </div>
                ))}
            </div>
        </>
    );
}