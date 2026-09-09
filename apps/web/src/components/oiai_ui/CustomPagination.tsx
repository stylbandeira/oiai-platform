// components/PaginationComponent.tsx
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
    PaginationEllipsis,
} from "@/components/ui/pagination";

interface PaginationMeta {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number;
    to: number;
}

interface PaginationComponentProps {
    paginationMeta: PaginationMeta | null;
    search: string;
    filterStatus: string;
    onPageChange: (page: number, search: string, filterStatus: string) => void;
}

export function CustomPagination({
    paginationMeta,
    search,
    filterStatus,
    onPageChange
}: PaginationComponentProps) {

    if (!paginationMeta || paginationMeta.last_page <= 1) return null;

    const { current_page, last_page, from, to, total } = paginationMeta;

    const handlePageChange = (page: number) => {
        if (page < 1 || page > last_page) return;
        onPageChange(page, search, filterStatus);
    };

    // Gera páginas visíveis
    const getVisiblePages = () => {
        const pages = [];
        const maxVisible = 5;

        let startPage = Math.max(1, current_page - Math.floor(maxVisible / 2));
        let endPage = Math.min(last_page, startPage + maxVisible - 1);

        if (endPage - startPage + 1 < maxVisible) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            pages.push(i);
        }

        return pages;
    };

    return (
        <div className="flex flex-col sm:flex-row items-center justify-between gap-3 mt-6">
            <div className="text-sm text-muted-foreground">
                Mostrando {from} a {to} de {total} resultados
            </div>

            <Pagination>
                <PaginationContent className="flex-wrap justify-center gap-1">
                    {/* Anterior */}
                    <PaginationItem>
                        <PaginationPrevious
                            onClick={() => handlePageChange(current_page - 1)}
                            className={current_page === 1 ?
                                "pointer-events-none opacity-50" :
                                "cursor-pointer hover:bg-accent"
                            }
                        />
                    </PaginationItem>

                    {/* Primeira página */}
                    {current_page > 3 && (
                        <>
                            <PaginationItem>
                                <PaginationLink
                                    onClick={() => handlePageChange(1)}
                                    className="cursor-pointer hover:bg-accent"
                                >
                                    1
                                </PaginationLink>
                            </PaginationItem>

                            {current_page > 4 && (
                                <PaginationItem>
                                    <PaginationEllipsis />
                                </PaginationItem>
                            )}
                        </>
                    )}

                    {/* Páginas visíveis */}
                    {getVisiblePages().map((page) => (
                        <PaginationItem key={page}>
                            <PaginationLink
                                isActive={current_page === page}
                                onClick={() => handlePageChange(page)}
                                className="cursor-pointer hover:bg-accent"
                            >
                                {page}
                            </PaginationLink>
                        </PaginationItem>
                    ))}

                    {/* Última página */}
                    {current_page < last_page - 2 && (
                        <>
                            {current_page < last_page - 3 && (
                                <PaginationItem>
                                    <PaginationEllipsis />
                                </PaginationItem>
                            )}

                            <PaginationItem>
                                <PaginationLink
                                    onClick={() => handlePageChange(last_page)}
                                    className="cursor-pointer hover:bg-accent"
                                >
                                    {last_page}
                                </PaginationLink>
                            </PaginationItem>
                        </>
                    )}

                    {/* Próximo */}
                    <PaginationItem>
                        <PaginationNext
                            onClick={() => handlePageChange(current_page + 1)}
                            className={current_page === last_page ?
                                "pointer-events-none opacity-50" :
                                "cursor-pointer hover:bg-accent"
                            }
                        />
                    </PaginationItem>
                </PaginationContent>
            </Pagination>
        </div>
    );
}