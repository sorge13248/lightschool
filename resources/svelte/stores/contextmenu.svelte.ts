/**
 * Svelte 5 runes-based context menu store for file context menus.
 * Replaces the legacy FraContextMenu class for the file manager context menu.
 */

interface ContextMenuState {
    visible: boolean;
    x: number;
    y: number;
    fileId: string;
    fileType: string;
    fileFav: number;
    fileInTrash: boolean;
}

class ContextMenuStore {
    state: ContextMenuState = $state({
        visible: false,
        x: 0,
        y: 0,
        fileId: '',
        fileType: '',
        fileFav: 0,
        fileInTrash: false,
    });

    show(data: Omit<ContextMenuState, 'visible'>): void {
        this.state = { visible: true, ...data };
    }

    hide(): void {
        this.state.visible = false;
    }
}

export const contextmenu = new ContextMenuStore();
