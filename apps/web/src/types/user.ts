import { Notification } from "./notification";

export interface User {
    id: number;
    type: UserType;
    name: string;
    status: "active" | "inactive" | "suspended";
    email?: string;
    points?: number;
    reputation: number;
    cpf: string;
    notifications: [];
    notificationList: Notification[];
    token?: string;
    activeCompanies?: [];
    pendingCompanies?: [];
    created_at: string;
    deleted_at: string | null;
}

export type UserType = "client" | "company" | "admin";