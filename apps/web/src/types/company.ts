export interface Company {
    id: number;
    name: string;
    email: string;
    cnpj: string;
    img: string;
    website?: string;
    full_address: string;
    total_products: number;
    phone: string;
    description: string;
    raw_address: string;
    webhookUrl?: string;
    status: 'active' | 'inactive' | 'pending';
    ownership_status: 'active' | 'inactive' | 'pending';
    created_at: string;
}