import { useEffect, useState } from 'react';
import { useLocation, useNavigate, useParams, useSearchParams } from 'react-router-dom';
import axios from 'axios';

export default function EmailVerificationPage() {

    const [status, setStatus] = useState('loading'); // loading, success, error

    const location = useLocation();
    const navigate = useNavigate();

    useEffect(() => {
        const queryParams = new URLSearchParams(location.search);
        const verifyUrl = queryParams.get('signed_url');

        if (!verifyUrl) {
            navigate('/');
            return;
        }

        const verifyEmail = async () => {
            try {

                const url = new URL(verifyUrl);
                const path = url.pathname + url.search;

                const response = await axios.get(verifyUrl);

                if (response.status === 200) {
                    setStatus('success');
                } else {
                    setStatus('error');
                }

                console.log(url);
            } catch (error) {
                setStatus('error');
            }
        };

        verifyEmail();
    }, [location, navigate]);

    return (
        <div className="flex flex-col items-center justify-center min-h-screen text-center p-6">
            {status === 'loading' && <p>Verificando seu e-mail...</p>}
            {status === 'success' && (
                <>
                    <h1 className="text-2xl font-bold mb-2">E-mail verificado com sucesso!</h1>
                    <p className="mb-4">Você já pode fazer login na plataforma.</p>
                    <a
                        href="/login"
                        className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                    >
                        Ir para o login
                    </a>
                </>
            )}
            {status === 'error' && (
                <>
                    <h1 className="text-2xl font-bold mb-2 text-red-600">Erro na verificação</h1>
                    <p className="mb-4">
                        O link de verificação está expirado ou inválido. Solicite outro.
                    </p>
                    <a
                        href="/reenviar-email"
                        className="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700"
                    >
                        Reenviar link de verificação
                    </a>
                </>
            )}
        </div>
    );
}
