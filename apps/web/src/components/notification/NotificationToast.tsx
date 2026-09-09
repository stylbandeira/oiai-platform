// components/NotificationToast.tsx
import { CheckCircle, X } from "lucide-react";
import { useEffect, useState } from "react";

interface NotificationToastProps {
    message: string;
    duration?: number;
    type?: "success" | "error" | "warning" | "info";
    onClose?: () => void;
}

export function NotificationToast({
    message,
    duration = 5000,
    type = "success",
    onClose
}: NotificationToastProps) {
    const [isVisible, setIsVisible] = useState(true);

    useEffect(() => {
        if (duration > 0) {
            const timer = setTimeout(() => {
                setIsVisible(false);
                if (onClose) onClose();
            }, duration);

            return () => clearTimeout(timer);
        }
    }, [duration, onClose]);

    useEffect(() => {
        setIsVisible(true);
    }, [message]);

    const handleClose = () => {
        setIsVisible(false);
        if (onClose) onClose();
    };

    if (!isVisible) return null;

    const typeConfig = {
        success: {
            bgColor: "bg-green-50",
            borderColor: "border-green-200",
            textColor: "text-green-800",
            icon: CheckCircle,
            iconColor: "text-green-500"
        },
        error: {
            bgColor: "bg-red-50",
            borderColor: "border-red-200",
            textColor: "text-red-800",
            icon: X,
            iconColor: "text-red-500"
        },
        warning: {
            bgColor: "bg-yellow-50",
            borderColor: "border-yellow-200",
            textColor: "text-yellow-800",
            icon: CheckCircle,
            iconColor: "text-yellow-500"
        },
        info: {
            bgColor: "bg-blue-50",
            borderColor: "border-blue-200",
            textColor: "text-blue-800",
            icon: CheckCircle,
            iconColor: "text-blue-500"
        }
    };

    const config = typeConfig[type];
    const Icon = config.icon;

    return (
        <div className="fixed top-4 right-4 z-50 animate-in slide-in-from-top-5 duration-300">
            <div className={`${config.bgColor} border ${config.borderColor} rounded-lg shadow-lg p-4 max-w-xs flex items-start gap-3`}>
                <Icon className={`${config.iconColor} flex-shrink-0 mt-0.5`} size={20} />
                <div className="flex-1">
                    <p className={`text-sm font-medium ${config.textColor}`}>{message}</p>
                </div>
                <button
                    onClick={handleClose}
                    className="text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0"
                    aria-label="Fechar notificação"
                >
                    <X size={16} />
                </button>
            </div>
        </div>
    );
}