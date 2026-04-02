import { apiGet, apiPost, apiPut, apiDelete } from './useApi';
import type { Section } from '../types/builder';

interface SaveResult {
  success: boolean;
  error?: string;
  freshSections?: Section[];
}

/**
 * Container-like section types that the backend recognizes without
 * needing a matching component in the DB.
 */
import { CONTAINER_TYPES } from '../utils/constants';

function parseJson(val: unknown): Record<string, unknown> {
  if (typeof val === 'string') {
    try { return JSON.parse(val); } catch { return {}; }
  }
  if (typeof val === 'object' && val !== null) return val as Record<string, unknown>;
  return {};
}

/**
 * Flatten a nested section tree (from API) into a flat array for the store.
 */
function flattenSections(tree: unknown[]): Section[] {
  const flat: Section[] = [];
  function walk(nodes: unknown[], parentId: string | null) {
    if (!Array.isArray(nodes)) return;
    for (const node of nodes) {
      const n = node as Record<string, unknown>;
      const section: Section = {
        id: String(n.id ?? ''),
        page_id: Number(n.page_id ?? 0),
        parent_id: parentId,
        section_type: String(n.section_type ?? ''),
        variant_id: String(n.variant_id ?? ''),
        content: parseJson(n.content) as Record<string, unknown>,
        style: parseJson(n.style) as Section['style'],
        layout: parseJson(n.layout) as Section['layout'],
        responsive: parseJson(n.responsive) as Section['responsive'],
        sort_order: Number(n.sort_order ?? 0),
        is_visible: n.is_visible !== false && n.is_visible !== 0 && n.enabled !== false && n.enabled !== 0,
        custom_css: String(n.custom_css ?? ''),
        custom_id: String(n.custom_id ?? ''),
      };
      flat.push(section);
      if (Array.isArray(n.children)) {
        walk(n.children, section.id);
      }
    }
  }
  walk(tree, null);
  return flat;
}

/**
 * Check if a section ID is client-side generated (not from the backend).
 */
function isClientId(id: string): boolean {
  return id.startsWith('s_');
}

/**
 * Save all page changes in a single batch, then reload fresh data from API.
 */
export async function savePageChanges(
  pageId: number,
  pageSlug: string,
  sections: Section[],
  originalSections: Section[],
  pageMeta?: { title?: string; slug?: string },
): Promise<SaveResult> {
  try {
    const originalIds = new Set(originalSections.map((s) => s.id));
    const currentIds = new Set(sections.map((s) => s.id));

    const deletedIds = originalSections
      .filter((s) => !currentIds.has(s.id) && !isClientId(s.id))
      .map((s) => s.id);

    // Separate sections into new (client-side IDs) and existing (backend IDs)
    const newSections = sections.filter((s) => isClientId(s.id));
    const existingSections = sections.filter((s) => !isClientId(s.id) && originalIds.has(s.id));

    // 1. Delete removed sections (only backend IDs)
    for (const id of deletedIds) {
      await apiDelete(`/sections/${id}`).catch(() => {});
    }

    // 2. Create new sections level-by-level (parents before children)
    // This handles any nesting depth, not just 2 levels.
    const idMap = new Map<string, string>(); // client-id → backend-id
    let remaining = [...newSections];
    let safetyLimit = 10; // prevent infinite loop

    while (remaining.length > 0 && safetyLimit-- > 0) {
      // Find sections whose parent is already created (in idMap) or is a backend ID or null
      const canCreate = remaining.filter((s) => {
        if (s.parent_id === null) return true;
        if (!isClientId(s.parent_id)) return true; // parent is backend ID
        if (idMap.has(s.parent_id)) return true; // parent was just created
        return false;
      });

      if (canCreate.length === 0) break; // stuck — orphan sections

      for (const section of canCreate) {
        const sectionType = CONTAINER_TYPES.includes(section.section_type)
          ? 'container'
          : section.section_type;

        let parentId: number | null = null;
        if (section.parent_id) {
          const mapped = idMap.get(section.parent_id);
          parentId = mapped ? Number(mapped) : (!isClientId(section.parent_id) ? Number(section.parent_id) : null);
        }

        try {
          const result = await apiPost<Record<string, unknown>>(`/pages/${pageId}/sections`, {
            section_type: sectionType,
            variant_id: section.variant_id || 'variant-01',
            parent_id: parentId,
            content: section.content,
            style: section.style,
            layout: section.layout,
            sort_order: section.sort_order,
            enabled: section.is_visible ? 1 : 0,
            custom_css: section.custom_css,
            custom_id: section.custom_id,
          });

          const resObj = result as Record<string, unknown>;
          const dataObj = resObj.data as Record<string, unknown> | undefined;
          const newId = String(resObj.id ?? dataObj?.id ?? '');
          if (newId && newId !== 'undefined') {
            idMap.set(section.id, newId);
          }
        } catch (e) {
          console.error(`Failed to create section ${section.section_type}:`, e);
        }
      }

      remaining = remaining.filter((s) => !idMap.has(s.id) && canCreate.indexOf(s) === -1);
    }

    // 3. Update existing sections that changed
    for (const section of existingSections) {
      const original = originalSections.find((s) => s.id === section.id);
      if (!original) continue;

      if (JSON.stringify(section.content) !== JSON.stringify(original.content)) {
        await apiPut(`/sections/${section.id}/content`, section.content as Record<string, unknown>);
      }

      if (JSON.stringify(section.style) !== JSON.stringify(original.style)) {
        await apiPut(`/sections/${section.id}/style`, section.style as Record<string, unknown>);
      }

      if (section.variant_id !== original.variant_id) {
        await apiPut(`/sections/${section.id}/variant`, { variant_id: section.variant_id });
      }

      if (JSON.stringify(section.layout) !== JSON.stringify(original.layout)) {
        await apiPut(`/sections/${section.id}/layout`, section.layout as Record<string, unknown>);
      }

      if (section.is_visible !== original.is_visible) {
        await apiPut(`/sections/${section.id}/toggle`, {});
      }
    }

    // 4. Reorder root sections (use backend IDs)
    const orderedIds = sections
      .filter((s) => s.parent_id === null)
      .sort((a, b) => a.sort_order - b.sort_order)
      .map((s) => idMap.get(s.id) ?? s.id)
      .filter((id) => !isClientId(id));

    if (orderedIds.length > 0) {
      await apiPost(`/pages/${pageId}/sections/reorder`, { order: orderedIds.map(Number) }).catch(() => {});
    }

    // 5. Update page meta
    if (pageMeta && (pageMeta.title || pageMeta.slug)) {
      await apiPut(`/pages/${pageId}`, pageMeta);
    }

    // 6. Reload fresh data from API to get real backend IDs
    let freshSections: Section[] | undefined;
    try {
      const res = await apiGet<Record<string, unknown>>(`/pages/${pageSlug}`);
      const pageData = (res as Record<string, unknown>).data ?? res;
      const raw = (pageData as Record<string, unknown>).sections;
      if (Array.isArray(raw)) {
        freshSections = flattenSections(raw);
      }
    } catch {
      // Non-fatal — save succeeded but couldn't refresh
    }

    return { success: true, freshSections };
  } catch (err: unknown) {
    return {
      success: false,
      error: err instanceof Error ? err.message : 'Failed to save',
    };
  }
}
