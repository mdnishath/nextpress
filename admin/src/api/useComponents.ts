import { useState, useEffect } from '@wordpress/element';
import { apiGet } from './useApi';
import type { Component, Variant } from '../types/builder';
import { BUILT_IN_COMPONENTS } from '../builder/LeftPanel/builtinComponents';

function extractArray<T>(res: unknown): T[] {
  if (Array.isArray(res)) return res;
  if (res && typeof res === 'object') {
    const obj = res as Record<string, unknown>;
    // Handle { success, data: { components: [...] } }
    if ('data' in obj && obj.data && typeof obj.data === 'object') {
      const d = obj.data as Record<string, unknown>;
      if ('components' in d && Array.isArray(d.components)) return d.components as T[];
      if (Array.isArray(d)) return d as T[];
    }
    // Handle { data: [...] }
    if ('data' in obj && Array.isArray(obj.data)) return obj.data as T[];
  }
  return [];
}

export function useComponents() {
  const [components, setComponents] = useState<Component[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);

    apiGet<unknown>('/components')
      .then((res) => {
        if (!cancelled) {
          const apiComps = extractArray<Component>(res);
          // Merge built-in components (avoid duplicates by slug)
          const apiSlugs = new Set(apiComps.map((c) => c.slug));
          const merged = [...apiComps, ...BUILT_IN_COMPONENTS.filter((c) => !apiSlugs.has(c.slug))];
          setComponents(merged);
        }
      })
      .catch((err: unknown) => {
        if (!cancelled) setError(err instanceof Error ? err.message : 'Failed to load components');
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, []);

  const getByCategory = (category: string) =>
    components.filter((c) => c.category === category);

  const categories = [...new Set(components.map((c) => c.category))];

  return { components, loading, error, getByCategory, categories };
}

export function useVariants(componentSlug: string) {
  const [variants, setVariants] = useState<Variant[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!componentSlug) {
      setVariants([]);
      return;
    }

    let cancelled = false;
    setLoading(true);

    apiGet<unknown>(`/components/${componentSlug}/variants`)
      .then((res) => {
        if (!cancelled) setVariants(extractArray<Variant>(res));
      })
      .catch(() => {
        if (!cancelled) setVariants([]);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [componentSlug]);

  return { variants, loading };
}
