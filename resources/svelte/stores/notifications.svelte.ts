import { v4 as uuidv4 } from 'uuid';

export interface Notification {
    id: string;
    text: string;
    type?: 'error' | 'success' | '';
    autoClose?: number;
}

/**
 * Svelte 5 runes-based notification store.
 *
 * Replaces the legacy FraNotifications class. Components read
 * `notifications.items` reactively and call `notifications.add()` /
 * `notifications.remove()` / `notifications.clear()` to manage toasts.
 */
class NotificationsStore {
    items: Notification[] = $state([]);

    add(text: string, opts?: Omit<Notification, 'id' | 'text'>): string {
        const id = uuidv4();
        this.items.push({ id, text, ...opts });
        return id;
    }

    remove(id: string): void {
        const idx = this.items.findIndex(n => n.id === id);
        if (idx !== -1) this.items.splice(idx, 1);
    }

    clear(): void {
        this.items.length = 0;
    }
}

export const notifications = new NotificationsStore();
