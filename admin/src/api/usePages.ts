import { useState, useEffect, useCallback } from '@wordpress/element';
import { apiGet, apiPost, apiPut, apiDelete } from './useApi';
import type { Page } from '../types/builder';

function extractArray<T>(res: unknown): T[] {
  if (Array.isArray(res)) return res;
  if (res && typeof res === 'object' && 'data' in res) {
    const d = (res as Record<string, unknown>).data;
    if (Array.isArray(d)) return d;
  }
  return [];
}

function extractData<T>(res: unknown): T {
  if (res && typeof res === 'object' && 'data' in res) {
    return (res as Record<string, unknown>).data as T;
  }
  return res as T;
}

export function usePages() {
  const [pages, setPages] = useState<Page[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchPages = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await apiGet<unknown>('/pages');
      setPages(extractArray<Page>(res));
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to load pages');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchPages();
  }, [fetchPages]);

  const createPage = async (data: { title: string; slug: string; status?: string }) => {
    const res = await apiPost<unknown>('/pages', data);
    const page = extractData<Page>(res);
    setPages((prev) => [...prev, page]);
    return page;
  };

  const updatePage = async (id: number, data: Partial<Page>) => {
    const res = await apiPut<unknown>(`/pages/${id}`, data);
    const page = extractData<Page>(res);
    setPages((prev) => prev.map((p) => (p.id === id ? page : p)));
    return page;
  };

  const deletePage = async (id: number) => {
    await apiDelete(`/pages/${id}`);
    setPages((prev) => prev.filter((p) => p.id !== id));
  };

  return { pages, loading, error, fetchPages, createPage, updatePage, deletePage };
}

export function usePage(slug: string) {
  const [page, setPage] = useState<(Page & { sections?: unknown[] }) | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!slug) return;

    let cancelled = false;
    setLoading(true);
    setError(null);

    apiGet<unknown>(`/pages/${slug}`)
      .then((res) => {
        if (!cancelled) setPage(extractData<Page & { sections?: unknown[] }>(res));
      })
      .catch((err: unknown) => {
        if (!cancelled) setError(err instanceof Error ? err.message : 'Failed to load page');
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [slug]);

  return { page, loading, error };
}
