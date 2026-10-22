import React, { useEffect, useRef, useState } from 'react';
import { Bell, X, Zap } from 'lucide-react';
import { formatDistanceToNow } from 'date-fns';
import { ptBR } from 'date-fns/locale';
import api from '@/lib/api';
import { useNavigate } from 'react-router-dom';
import { getNotificationLink } from "@/utils/notificationsLink";
import { Notification } from '@/types/notification';
import { toast } from 'sonner';

interface Props {
    notifications: Notification[];
    unreadCount: number;
}

const NotificationsBell: React.FC<Props> = ({ notifications, unreadCount }) => {
    const [isOpen, setIsOpen] = useState(false);
    const [localNotifications, setLocalNotifications] = useState(notifications);
    const [localUnreadCount, setLocalUnreadCount] = useState(unreadCount);
    const [isMarkingAsRead, setIsMarkingAsRead] = useState(false);
    const notificationRef = useRef<HTMLDivElement>(null);
    const navigate = useNavigate();

    useEffect(() => {
        setLocalNotifications(notifications);
        setLocalUnreadCount(unreadCount);
    }, [notifications, unreadCount]);

    useEffect(() => {
        if (!isOpen) return;

        const handleClickOutside = (event: MouseEvent) => {
            if (!notificationRef.current?.contains(event.target as Node)) {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);

        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, [isOpen]);

    const markNotificationsAsRead = async () => {
        if (isMarkingAsRead || localUnreadCount === 0) return;

        setIsMarkingAsRead(true);
        try {
            await api.post('events/check-all');
            setLocalNotifications((current) => current.map((notification) => ({
                ...notification,
                checked: true,
            })));
            setLocalUnreadCount(0);
        } catch {
            toast.error('Não foi possível marcar as notificações como lidas.');
        } finally {
            setIsMarkingAsRead(false);
        }
    };

    const handleToggle = () => {
        const opening = !isOpen;
        setIsOpen(opening);

        if (opening) {
            void markNotificationsAsRead();
        }
    };

    const handleMarkAsRead = (id: number) => {
        void api.put(`events/${id}`, {
            checked: true,
        });
    };

    const handleOpenLink = async (
        event: React.MouseEvent,
        notification: Notification,
    ) => {
        event.stopPropagation();

        const link = getNotificationLink(notification);


        setIsOpen(false);
        navigate(link);
    };

    const formatDate = (dateString: string) => {
        try {
            return formatDistanceToNow(new Date(dateString), {
                addSuffix: true,
                locale: ptBR
            });
        } catch {
            return dateString;
        }
    };

    return (
        <div ref={notificationRef} className="relative">
            {/* Botão do sino */}
            <button
                onClick={handleToggle}
                className="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors relative"
            >
                <Bell className={`w-5 h-5 ${localUnreadCount > 0 ? 'text-red-500' : 'text-gray-600'}`} />

                {localUnreadCount > 0 && (
                    <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                        {localUnreadCount > 9 ? '9+' : localUnreadCount}
                    </span>
                )}

                {localUnreadCount > 0 && (
                    <span className="font-medium text-sm text-red-500">
                        {localUnreadCount}
                    </span>
                )}
            </button>

            {/* Dropdown */}
            {isOpen && (
                <div className="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border z-50 animate-in slide-in-from-top-5">
                    {/* Cabeçalho */}
                    <div className="p-4 border-b flex justify-between items-center">
                        <h3 className="font-semibold">Notificações</h3>
                        <div className="flex gap-2">
                            <button
                                onClick={() => setIsOpen(false)}
                                className="text-gray-500 hover:text-gray-700"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    {/* Lista */}
                    <div className="max-h-96 overflow-y-auto">
                        {localNotifications.length === 0 ? (
                            <div className="p-6 text-center text-gray-500">
                                <Bell className="w-10 h-10 mx-auto mb-2 text-gray-300" />
                                <p>Nenhuma notificação</p>
                            </div>
                        ) : (
                            localNotifications.map((notif) => (
                                <div
                                    key={notif.id}
                                    className={`p-4 border-b hover:bg-gray-50 cursor-pointer ${notif.checked ? 'opacity-70' : 'bg-blue-50'}`}
                                    onClick={() => handleMarkAsRead(notif.id)}
                                >
                                    <div className="flex gap-3">
                                        <div className="flex-shrink-0">
                                            {notif.type === 'points' ? (
                                                <Zap className="w-5 h-5 text-yellow-500" />
                                            ) : (
                                                <Bell className="w-5 h-5 text-blue-500" />
                                            )}
                                        </div>

                                        <div className="flex-1">
                                            <p
                                                onClick={(event) => handleOpenLink(event, notif)}
                                                className="text-sm">{notif.description}
                                            </p>

                                            <div className="flex justify-between items-center mt-2">
                                                <span className="text-xs text-gray-500">
                                                    {formatDate(notif.created_at)}
                                                </span>

                                                {notif.points > 0 && (
                                                    <span className="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">
                                                        +{notif.points} pontos
                                                    </span>
                                                )}
                                            </div>
                                        </div>

                                        {!notif.is_new && (
                                            <div className="w-2 h-2 bg-red-500 rounded-full flex-shrink-0 mt-1" />
                                        )}
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            )
            }
        </div >
    );
};

export default NotificationsBell;
