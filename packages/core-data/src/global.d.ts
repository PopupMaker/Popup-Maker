declare global {
    interface Window {
        wpApiSettings?: {
            nonce: string;
            root?: string;
            [key: string]: unknown;
        };
    }
}

export {}; 