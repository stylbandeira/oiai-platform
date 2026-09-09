// src/components/admin/TableFilters.tsx
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Search } from "lucide-react";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import { Button } from "@/components/ui/button";
import { Filter } from "lucide-react";
import { useState } from "react";

interface FilterOption {
    value: string;
    label: string;
}

interface TableFiltersProps {
    searchPlaceholder: string;
    searchValue: string;
    onSearchChange: (value: string) => void;
    filters?: {
        key: string;
        placeholder: string;
        value: string;
        onChange: (value: string) => void;
        options: FilterOption[];
    }[];
    showMobileFilters?: boolean;
}

export function TableFilters({
    searchPlaceholder,
    searchValue,
    onSearchChange,
    filters = [],
    showMobileFilters = true
}: TableFiltersProps) {
    const [isMobileFiltersOpen, setIsMobileFiltersOpen] = useState(false);

    const renderFiltersContent = () => (
        <>
            <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                <Input
                    placeholder={searchPlaceholder}
                    className="pl-10"
                    value={searchValue}
                    onChange={(e) => onSearchChange(e.target.value)}
                />
            </div>

            {filters.map((filter) => (
                <Select key={filter.key} value={filter.value} onValueChange={filter.onChange}>
                    <SelectTrigger className="w-full sm:w-48">
                        <SelectValue placeholder={filter.placeholder} />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos</SelectItem>
                        {filter.options.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            ))}
        </>
    );

    return (
        <>
            {/* Desktop Filters */}
            <div className="hidden md:flex flex-col sm:flex-row gap-4">
                {renderFiltersContent()}
            </div>

            {/* Mobile Filters Button */}
            {showMobileFilters && (
                <div className="md:hidden">
                    <Sheet open={isMobileFiltersOpen} onOpenChange={setIsMobileFiltersOpen}>
                        <SheetTrigger asChild>
                            <Button variant="outline" className="w-full">
                                <Filter className="w-4 h-4 mr-2" />
                                Filtros
                                {filters.some(f => f.value !== "all") && (
                                    <span className="ml-2 bg-primary text-primary-foreground rounded-full w-5 h-5 text-xs flex items-center justify-center">
                                        {filters.filter(f => f.value !== "all").length}
                                    </span>
                                )}
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="bottom" className="rounded-t-2xl">
                            <div className="space-y-4 py-4">
                                <h3 className="font-semibold text-lg">Filtros</h3>
                                <div className="space-y-3">
                                    {renderFiltersContent()}
                                </div>
                                <Button
                                    className="w-full mt-4"
                                    onClick={() => setIsMobileFiltersOpen(false)}
                                >
                                    Aplicar Filtros
                                </Button>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>
            )}
        </>
    );
}