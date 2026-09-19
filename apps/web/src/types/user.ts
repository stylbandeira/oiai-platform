import { Notification } from "./notification";
import type { components } from "./api.generated";

export interface User extends Omit<components["schemas"]["User"], "type" | "notifications"> {
    id: number;
    type: UserType;
    name: string;
    status: "active" | "inactive" | "suspended";
    email?: string;
    points?: number;
    reputation: number;
    cpf: string;
    notifications: Notification[];
    notificationList: Notification[];
    token?: string;
    activeCompanies?: components["schemas"]["Company"][];
    pendingCompanies?: components["schemas"]["Company"][];
    created_at: string;
    deleted_at: string | null;
}

export type UserType = "client" | "company" | "admin";
