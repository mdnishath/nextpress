import { create } from 'zustand';
import type { Section, Breakpoint, Page, ResponsiveString } from '../types/builder';
import { useHistoryStore } from './historyStore';
import { setValueForBreakpoint } from '../utils/responsive';

interface BuilderState {
  // Page data
  pageId: number | null;
  pageTitle: string;
  pageSlug: string;
  pageStatus: 'draft' | 'published';

  // Sections (flat array — tree built on render)
  sections: Section[];
  originalSections: Section[]; // Snapshot from last save/load for diff

  // Selection
  selectedSectionId: string | null;
  hoveredSectionId: string | null;

  // Responsive
  breakpoint: Breakpoint;

  // Dirty state
  isDirty: boolean;
  isSaving: boolean;
  isLoading: boolean;
  lastSaved: string | null;
  error: string | null;

  // Actions — page
  setPage: (page: Partial<Page>) => void;
  setLoading: (loading: boolean) => void;
  setError: (error: string | null) => void;
  setSaving: (saving: boolean) => void;
  markSaved: () => void;
  markDirty: () => void;

  // Actions — sections
  setSections: (sections: Section[]) => void;
  snapshotOriginal: () => void;
  addSection: (section: Section, parentId?: string | null, position?: number) => void;
  removeSection: (id: string) => void;
  duplicateSection: (id: string) => string;
  moveSection: (id: string, newParentId: string | null, newIndex: number) => void;
  reorderSections: (orderedIds: string[]) => void;

  // Actions — section updates
  updateContent: (id: string, content: Record<string, unknown>) => void;
  updateStyle: (id: string, style: Record<string, unknown>) => void;
  updateResponsiveStyle: (id: string, key: string, breakpoint: Breakpoint, value: string) => void;
  updateLayout: (id: string, layout: Record<string, unknown>) => void;
  updateResponsiveLayout: (id: string, key: string, breakpoint: Breakpoint, value: string) => void;
  changeVariant: (id: string, variantId: string) => void;
  toggleSection: (id: string) => void;
  updateVisibility: (id: string, visibility: { desktop: boolean; tablet: boolean; mobile: boolean }) => void;

  // Actions — UI
  selectSection: (id: string | null) => void;
  hoverSection: (id: string | null) => void;
  setBreakpoint: (bp: Breakpoint) => void;

  // Helpers
  getSection: (id: string) => Section | undefined;
  getRootSections: () => Section[];
  getChildren: (parentId: string) => Section[];
  getSectionTree: () => Section[];
}

function generateId(): string {
  return `s_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
}

function pushHistory(sections: Section[]) {
  useHistoryStore.getState().pushState(sections);
}

export const useBuilderStore = create<BuilderState>((set, get) => ({
  // Initial state
  pageId: null,
  pageTitle: '',
  pageSlug: '',
  pageStatus: 'draft',
  sections: [],
  originalSections: [],
  selectedSectionId: null,
  hoveredSectionId: null,
  breakpoint: 'desktop',
  isDirty: false,
  isSaving: false,
  isLoading: false,
  lastSaved: null,
  error: null,

  // Page actions
  setPage: (page) => set((state) => ({
    pageId: page.id ?? state.pageId,
    pageTitle: page.title ?? state.pageTitle,
    pageSlug: page.slug ?? state.pageSlug,
    pageStatus: (page.status as 'draft' | 'published') ?? state.pageStatus,
  })),

  setLoading: (loading) => set({ isLoading: loading }),
  setError: (error) => set({ error }),
  setSaving: (saving) => set({ isSaving: saving }),
  markSaved: () => set({ isDirty: false, isSaving: false, lastSaved: new Date().toISOString() }),
  markDirty: () => set({ isDirty: true }),

  // Section actions
  setSections: (sections) => set({ sections }),
  snapshotOriginal: () => set((state) => ({ originalSections: state.sections.map((s) => ({ ...s })) })),

  addSection: (section, parentId = null, position) => {
    const state = get();
    pushHistory(state.sections);

    const newSection: Section = {
      ...section,
      id: section.id || generateId(),
      parent_id: parentId ?? null,
      sort_order: position ?? state.sections.length,
    };

    // Calculate sort_order based on siblings
    const siblings = state.sections.filter((s) => s.parent_id === parentId);
    if (position !== undefined) {
      // Shift siblings at or after position
      const updated = state.sections.map((s) => {
        if (s.parent_id === parentId && s.sort_order >= position) {
          return { ...s, sort_order: s.sort_order + 1 };
        }
        return s;
      });
      newSection.sort_order = position;
      set({ sections: [...updated, newSection], isDirty: true, selectedSectionId: newSection.id });
    } else {
      newSection.sort_order = siblings.length;
      set({ sections: [...state.sections, newSection], isDirty: true, selectedSectionId: newSection.id });
    }
  },

  removeSection: (id) => {
    const state = get();
    pushHistory(state.sections);

    // Remove section and all descendants
    const idsToRemove = new Set<string>();
    const collectDescendants = (parentId: string) => {
      idsToRemove.add(parentId);
      state.sections
        .filter((s) => s.parent_id === parentId)
        .forEach((s) => collectDescendants(s.id));
    };
    collectDescendants(id);

    const filtered = state.sections.filter((s) => !idsToRemove.has(s.id));
    set({
      sections: filtered,
      isDirty: true,
      selectedSectionId: state.selectedSectionId === id ? null : state.selectedSectionId,
    });
  },

  duplicateSection: (id) => {
    const state = get();
    pushHistory(state.sections);

    const source = state.sections.find((s) => s.id === id);
    if (!source) return id;

    const newId = generateId();
    const duplicate: Section = {
      ...source,
      id: newId,
      sort_order: source.sort_order + 1,
      content: { ...source.content },
      style: { ...source.style },
      layout: { ...source.layout },
      responsive: { ...source.responsive },
    };

    // Shift siblings after this position
    const updated = state.sections.map((s) => {
      if (s.parent_id === source.parent_id && s.sort_order > source.sort_order) {
        return { ...s, sort_order: s.sort_order + 1 };
      }
      return s;
    });

    set({ sections: [...updated, duplicate], isDirty: true, selectedSectionId: newId });
    return newId;
  },

  moveSection: (id, newParentId, newIndex) => {
    const state = get();
    pushHistory(state.sections);

    const section = state.sections.find((s) => s.id === id);
    if (!section) return;

    // Prevent nesting beyond 5 levels
    let depth = 0;
    let parentId = newParentId;
    while (parentId) {
      depth++;
      if (depth > 5) return;
      const parent = state.sections.find((s) => s.id === parentId);
      parentId = parent?.parent_id ?? null;
    }

    const updated = state.sections.map((s) => {
      if (s.id === id) {
        return { ...s, parent_id: newParentId, sort_order: newIndex };
      }
      // Re-index siblings in old parent
      if (s.parent_id === section.parent_id && s.sort_order > section.sort_order && s.id !== id) {
        return { ...s, sort_order: s.sort_order - 1 };
      }
      // Shift siblings in new parent
      if (s.parent_id === newParentId && s.sort_order >= newIndex && s.id !== id) {
        return { ...s, sort_order: s.sort_order + 1 };
      }
      return s;
    });

    set({ sections: updated, isDirty: true });
  },

  reorderSections: (orderedIds) => {
    const state = get();
    pushHistory(state.sections);

    const updated = state.sections.map((s) => {
      const newIndex = orderedIds.indexOf(s.id);
      if (newIndex !== -1) {
        return { ...s, sort_order: newIndex };
      }
      return s;
    });

    set({ sections: updated, isDirty: true });
  },

  // Section update actions
  updateContent: (id, content) => {
    const state = get();
    pushHistory(state.sections);
    set({
      sections: state.sections.map((s) =>
        s.id === id ? { ...s, content: { ...s.content, ...content } } : s
      ),
      isDirty: true,
    });
  },

  updateStyle: (id, style) => {
    const state = get();
    pushHistory(state.sections);
    set({
      sections: state.sections.map((s) =>
        s.id === id ? { ...s, style: { ...s.style, ...style } } : s
      ),
      isDirty: true,
    });
  },

  updateResponsiveStyle: (id, key, breakpoint, value) => {
    const state = get();
    pushHistory(state.sections);
    set({
      sections: state.sections.map((s) => {
        if (s.id !== id) return s;
        const currentVal = s.style[key] as ResponsiveString | undefined;
        const newVal = setValueForBreakpoint(currentVal, breakpoint, value);
        return { ...s, style: { ...s.style, [key]: newVal } };
      }),
      isDirty: true,
    });
  },

  updateLayout: (id, layout) => {
    const state = get();
    pushHistory(state.sections);
    set({
      sections: state.sections.map((s) =>
        s.id === id ? { ...s, layout: { ...s.layout, ...layout } } : s
      ),
      isDirty: true,
    });
  },

  updateResponsiveLayout: (id, key, breakpoint, value) => {
    const state = get();
    pushHistory(state.sections);
    set({
      sections: state.sections.map((s) => {
        if (s.id !== id) return s;
        const currentVal = (s.layout as Record<string, unknown>)[key] as ResponsiveString | undefined;
        const newVal = setValueForBreakpoint(currentVal, breakpoint, value);
        return { ...s, layout: { ...s.layout, [key]: newVal } };
      }),
      isDirty: true,
    });
  },

  updateVisibility: (id, visibility) => {
    const state = get();
    pushHistory(state.sections);
    set({
      sections: state.sections.map((s) =>
        s.id === id ? { ...s, responsiveVisibility: visibility } : s
      ),
      isDirty: true,
    });
  },

  changeVariant: (id, variantId) => {
    const state = get();
    pushHistory(state.sections);
    set({
      sections: state.sections.map((s) =>
        s.id === id ? { ...s, variant_id: variantId } : s
      ),
      isDirty: true,
    });
  },

  toggleSection: (id) => {
    const state = get();
    pushHistory(state.sections);
    set({
      sections: state.sections.map((s) =>
        s.id === id ? { ...s, is_visible: !s.is_visible } : s
      ),
      isDirty: true,
    });
  },

  // UI actions
  selectSection: (id) => set({ selectedSectionId: id }),
  hoverSection: (id) => set({ hoveredSectionId: id }),
  setBreakpoint: (bp) => set({ breakpoint: bp }),

  // Helpers
  getSection: (id) => get().sections.find((s) => s.id === id),

  getRootSections: () =>
    get()
      .sections.filter((s) => s.parent_id === null)
      .sort((a, b) => a.sort_order - b.sort_order),

  getChildren: (parentId) =>
    get()
      .sections.filter((s) => s.parent_id === parentId)
      .sort((a, b) => a.sort_order - b.sort_order),

  getSectionTree: () => {
    const { sections } = get();
    const buildTree = (parentId: string | null): Section[] =>
      sections
        .filter((s) => s.parent_id === parentId)
        .sort((a, b) => a.sort_order - b.sort_order)
        .map((s) => ({ ...s, children: buildTree(s.id) }));
    return buildTree(null);
  },
}));
