/**
 * WordPress localized data injected via wp_localize_script().
 */
interface NpbAdminData {
  apiUrl: string;
  nonce: string;
  adminUrl: string;
  pluginUrl: string;
  version: string;
  locale: string;
  userId: number;
}

declare global {
  interface Window {
    npbAdmin: NpbAdminData;
  }
  const npbAdmin: NpbAdminData;
}

export {};
