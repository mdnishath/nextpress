/**
 * Generate a unique section ID.
 * Uses timestamp + counter + random to guarantee uniqueness even in rapid succession.
 */
let _idCounter = 0;
export function generateSectionId(): string {
  _idCounter++;
  return `s_${Date.now()}_${_idCounter}_${Math.random().toString(36).slice(2, 8)}`;
}

/**
 * Debounce a function call.
 */
export function debounce<T extends (...args: unknown[]) => void>(fn: T, ms: number): T {
  let timer: ReturnType<typeof setTimeout>;
  return ((...args: unknown[]) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), ms);
  }) as T;
}

/**
 * Format a date string relative to now.
 */
export function timeAgo(dateStr: string): string {
  const diff = Date.now() - new Date(dateStr).getTime();
  const seconds = Math.floor(diff / 1000);

  if (seconds < 60) return 'just now';
  if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
  return `${Math.floor(seconds / 86400)}d ago`;
}

/**
 * Classnames utility (lightweight clsx alternative).
 */
export function cn(...args: (string | false | null | undefined)[]): string {
  return args.filter(Boolean).join(' ');
}

/**
 * Safe JSON parse — returns object or empty.
 */
export function parseJson(val: unknown): Record<string, unknown> {
  if (typeof val === 'string') {
    try { return JSON.parse(val); } catch { return {}; }
  }
  if (typeof val === 'object' && val !== null) return val as Record<string, unknown>;
  return {};
}

/**
 * Flatten a nested section tree (from API) into a flat array for the store.
 */
export function flattenSections(tree: unknown[]): import('../types/builder').Section[] {
  const flat: import('../types/builder').Section[] = [];
  function walk(nodes: unknown[], parentId: string | null) {
    if (!Array.isArray(nodes)) return;
    for (const node of nodes) {
      const n = node as Record<string, unknown>;
      const section = {
        id: String(n.id ?? ''),
        page_id: Number(n.page_id ?? 0),
        parent_id: parentId,
        section_type: String(n.section_type ?? ''),
        variant_id: String(n.variant_id ?? ''),
        content: parseJson(n.content) as Record<string, unknown>,
        style: parseJson(n.style),
        layout: parseJson(n.layout),
        sort_order: Number(n.sort_order ?? 0),
        is_visible: n.is_visible !== false && n.is_visible !== 0 && n.enabled !== false && n.enabled !== 0,
        custom_css: String(n.custom_css ?? ''),
        custom_id: String(n.custom_id ?? ''),
      } as import('../types/builder').Section;
      flat.push(section);
      if (Array.isArray(n.children)) {
        walk(n.children, section.id);
      }
    }
  }
  walk(tree, null);
  return flat;
}
