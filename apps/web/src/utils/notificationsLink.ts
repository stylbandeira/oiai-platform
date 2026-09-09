import { Notification } from "@/types/notification";

export const getNotificationLink = (notification: Notification): string => {
    switch (notification.link) {
        case "company_ownership_request":
            return `/admin/users/edit/${notification.entity_id}`;

        default:
            return "/";
    }
}