import MainLayout from '@/Layouts/MainLayout';
import CompanyForm from '@/Components/MainComponents/Company/CompanyForm';

export default function CreateCompany(props) {

    const handleClick = () => {
        console.log('Botão');
    }

    const handleSuccess = (address) => {
        console.log('Endereço criado:', address);
    }

    return (
        <>
            <div className="flex justify-content-center">
                <CompanyForm onSuccess={handleSuccess}>

                </CompanyForm>
            </div>
        </>
    );
}
CreateCompany.layout = page => <MainLayout children={page} />
