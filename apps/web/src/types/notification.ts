export interface Notification {
    'id': number,
    'title': string,
    'description': string,
    'where': string,
    'type': string,
    'entity_type': string,
    'entity_id': string,
    'points': number,
    'link': string,
    'is_new': boolean,
    'created_at': string,
}

export type UserType = "client" | "company" | "admin";