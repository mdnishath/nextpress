import { useState, useEffect } from '@wordpress/element';
import { apiGet } from './useApi';
import type { Theme, ButtonPreset } from '../types/builder';

function extractData<T>(res: unknown): T | null {
  if (res && typeof res === 'object' && 'data' in res) {
    return (res as Record<string, unknown>).data as T;
  }
  return res as T;
}

function extractArray<T>(res: unknown): T[] {
  if (Array.isArray(res)) return res;
  if (res && typeof res === 'object' && 'data' in res) {
    const d = (res as Record<string, unknown>).data;
    if (Array.isArray(d)) return d;
  }
  return [];
}

export function useTheme() {
  const [theme, setTheme] = useState<Theme | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);

    apiGet<unknown>('/theme')
      .then((res) => {
        if (!cancelled) setTheme(extractData<Theme>(res));
      })
      .catch(() => {})
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, []);

  return { theme, loading };
}

export function useButtonPresets() {
  const [presets, setPresets] = useState<ButtonPreset[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);

    apiGet<unknown>('/buttons')
      .then((res) => {
        if (!cancelled) setPresets(extractArray<ButtonPreset>(res));
      })
      .catch(() => {})
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, []);

  return { presets, loading };
}
