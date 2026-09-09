import FormWrapper from '@/Components/MainComponents/FormWrapper';

export default function ProductForm({ product }) {
    const fields = [
        { name: 'name', label: 'Nome do Produto' },
        { name: 'price', label: 'Preço', type: 'number' },
    ];

    const handleSubmit = ({ data, put }) => {
        put(route('products.update', product.id));
    };

    return (
        <div>
            <h1 className="text-2xl font-bold mb-4">Editar Produto</h1>
            <FormWrapper model={product} fields={fields} onSubmit={handleSubmit} />
        </div>
    );
}
