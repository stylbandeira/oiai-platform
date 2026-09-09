import MainLayout from '@/Layouts/MainLayout';
import PrimaryButton from '@/Components/Buttons/PrimaryButton';
import IconButton from '@/Components/Buttons/IconButton';

export default function Welcome(props) {
    const empresas = {
        data: []
    };

    const handleClick = () => {
        console.log('Botão');
    }
    return (
        <>
            <div className="flex justify-content-center">
                <a href="/" rel="noopener noreferrer">
                    <img className="object-contain h-32" src="/storage/img/primary-logo.png" alt="Logo" />
                </a>
            </div>

            <h1>Empresas</h1>
            <div className="container p-2">

                <IconButton variant="add" />
                <IconButton variant="remove" />
                <IconButton variant="disabled" />


            </div>
        </>
    );
}
Welcome.layout = page => <MainLayout children={page} />
