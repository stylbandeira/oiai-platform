import FormWrapper from '@/Components/MainComponents/FormWrapper';

export default function CompanyForm() {
    const fields = [
        { name: 'company.name', label: 'Nome da Empresa' },
        { name: 'company.email', label: 'E-mail da Empresa' },
        { name: 'company.cnpj', label: 'CNPJ da Empresa' },
        { name: 'company.img', label: 'Logomarca', type: 'image' },

        { name: 'address.country', label: 'País' },
        { name: 'address.area', label: 'Estado' },
        { name: 'address.city', label: 'Cidade' },
        { name: 'address.street', label: 'Rua' },
        { name: 'address.number', label: 'Número' },
        { name: 'address.complement', label: 'Complemento' },
    ];

    const handleSubmit = ({ data, post }) => {
        console.log(data);
        post(route('company.store'));
    };

    return (
        <div>
            <h1 className="text-2xl font-bold mb-4 text-center">Criar Empresa</h1>
            <FormWrapper fields={fields} onSubmit={handleSubmit} />
        </div>
    );
}
