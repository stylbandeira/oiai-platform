// utils/urlUtils.ts
export const getFullImageUrl = (path: string | null | undefined): string | null => {
    if (!path) return null;

    // Se já é URL completa, retorna como está
    if (path.startsWith('http')) return path;

    // Se começa com /, adiciona a origem
    if (path.startsWith('/')) {
        return `${window.location.origin}${path}`;
    }

    // Para caminhos relativos sem /
    return `${window.location.origin}/storage/${path}`;
};