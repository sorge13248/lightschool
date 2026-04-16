/**
 * Window interface augmentation for globals loaded outside the module graph.
 *
 * Gives TypeScript proper types for window globals so components
 * don't need `(window as any).*` casts.
 */

declare global {
    interface Window {
        /** i18n dictionary loaded by /lang/{locale}.js */
        LANGUAGE: Record<string, string>;

        /** Uppy uploader — loaded lazily from /js/uppy.min.js */
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        Uppy: any;

        /** Quill rich-text editor — loaded lazily from /js/quill.js */
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        Quill: any;
    }
}

export {};
