// src/components/AuthWrapper.jsx
import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '@/lib/api';

export default function AuthWrapper({ children }) {
    const navigate = useNavigate();

    useEffect(() => {
        const checkVerification = async () => {
            try {
                const response = await api.get('/api/user');
                if (!response.data.email_verified_at) {
                    navigate('/email-verification');
                }
            } catch (error) {
                navigate('/login');
            }
        };

        checkVerification();
    }, [navigate]);

    return children;
}