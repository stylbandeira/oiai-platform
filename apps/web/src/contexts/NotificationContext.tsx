import { NotificationToast } from '@/components/notification/NotificationToast';
import { createContext, useContext, useState, ReactNode } from 'react';

interface NotificationContextType {
    showNotification: (message: string, type?: 'success' | 'error' | 'warning' | 'info', duration?: number) => void;
    hideNotification: () => void;
}

const NotificationContext = createContext<NotificationContextType | undefined>(undefined);

export function NotificationProvider({ children }: { children: ReactNode }) {
    const [notification, setNotification] = useState<{
        message: string;
        type: 'success' | 'error' | 'warning' | 'info';
        duration: number;
        key: number;
    } | null>(null);

    const showNotification = (
        message: string,
        type: 'success' | 'error' | 'warning' | 'info' = 'success',
        duration: number = 5000
    ) => {
        setNotification({
            message,
            type,
            duration,
            key: Date.now() // Força recriação do componente
        });
    };

    const hideNotification = () => {
        setNotification(null);
    };

    return (
        <NotificationContext.Provider value={{ showNotification, hideNotification }}>
            {children}
            {notification && (
                <NotificationToast
                    key={notification.key}
                    message={notification.message}
                    type={notification.type}
                    duration={notification.duration}
                    onClose={hideNotification}
                />
            )}
        </NotificationContext.Provider>
    );
}

export const useNotification = () => {
    const context = useContext(NotificationContext);
    if (!context) {
        throw new Error('useNotification deve ser usado dentro de NotificationProvider');
    }
    return context;
};