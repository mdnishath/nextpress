import { useEffect, useCallback, useState, useRef } from '@wordpress/element';
import {
  DndContext,
  DragOverlay,
  pointerWithin,
  PointerSensor,
  useSensor,
  useSensors,
  type DragStartEvent,
  type DragEndEvent,
  type DragOverEvent,
} from '@dnd-kit/core';
import { Toolbar } from './Toolbar';
import { LeftPanel } from './LeftPanel/LeftPanel';
import { Canvas } from './CenterPanel/Canvas';
import { Toast } from '../components/Toast';
import { Confirm } from '../components/Confirm';
import { useBuilderStore } from '../store/builderStore';
import { useUIStore } from '../store/uiStore';
import { performUndo, performRedo } from '../store/historyStore';
import { apiGet } from '../api/useApi';
import { savePageChanges } from '../api/savePage';
import { generateSectionId } from '../utils/helpers';
import { getDefaultStyle } from '../utils/constants';
import { loadAllUsedFonts } from '../utils/fontLoader';
import type { Page, Section, Component } from '../types/builder';
import { ErrorBoundary } from '../components/ErrorBoundary';
import { logger } from '../utils/logger';

function parseJson(val: unknown): Record<string, unknown> {
  if (typeof val === 'string') {
    try { return JSON.parse(val); } catch { return {}; }
  }
  if (typeof val === 'object' && val !== null) return val as Record<string, unknown>;
  return {};
}

/**
 * Flatten a nested section tree (from API) into a flat array for the store.
 * The API returns sections as a tree with `children`, but the store uses a flat array.
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
        is_visible: n.is_visible !== false && n.is_visible !== 0,
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

interface PageBuilderProps {
  pageSlug: string;
}

export function PageBuilder({ pageSlug }: PageBuilderProps) {
  const {
    setPage,
    setSections,
    setLoading,
    setError,
    sections,
    selectSection,
    selectedSectionId,
    removeSection,
    duplicateSection,
    addSection,
    moveSection,
    snapshotOriginal,
    originalSections,
    pageId: storePageId,
    setSaving,
    markSaved,
    isSaving,
    isLoading,
    error,
    isDirty,
  } = useBuilderStore();

  const { previewMode, addToast } = useUIStore();

  // ─── DnD Setup (shared across LeftPanel + Canvas) ───
  // Must be before any early returns to satisfy Rules of Hooks.
  const dndSensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
  );
  const [dragActiveId, setDragActiveId] = useState<string | null>(null);
  const [dragLabel, setDragLabel] = useState<string>('');
  const [dropTargetId, setDropTargetId] = useState<string | null>(null);

  const handleDragStart = useCallback((event: DragStartEvent) => {
    setDragActiveId(String(event.active.id));
    const data = event.active.data.current;
    if (data?.type === 'palette-component') {
      setDragLabel((data.component as Component)?.name || 'Component');
    } else {
      // Existing section being moved
      const sec = useBuilderStore.getState().sections.find((s) => s.id === event.active.id);
      setDragLabel(sec?.section_type?.replace(/_/g, ' ')?.replace(/\b\w/g, (c: string) => c.toUpperCase()) || 'Section');
    }
  }, []);

  const handleDragOver = useCallback((event: DragOverEvent) => {
    setDropTargetId(event.over ? String(event.over.id) : null);
  }, []);

  const handleDragEnd = useCallback(
    (event: DragEndEvent) => {
      setDragActiveId(null);
      setDragLabel('');
      setDropTargetId(null);
      const { active, over } = event;
      if (!over) return;

      const pageIdVal = useBuilderStore.getState().pageId;
      if (!pageIdVal) return;

      const activeData = active.data.current;
      const overData = over.data.current;
      const overId = String(over.id);
      const rootIds = useBuilderStore.getState().getRootSections().map((s) => s.id);

      // ── Palette component → drop target ──
      if (activeData?.type === 'palette-component') {
        const comp = activeData.component as Component;
        let defContent = comp.default_content || {};
        if (typeof defContent === 'string') {
          try { defContent = JSON.parse(defContent); } catch { defContent = {}; }
        }
        const isContainer = comp.is_container || comp.slug === 'container';

        // Dropped on a container → add directly inside
        if (overData?.type === 'container') {
          addSection({
            id: generateSectionId(),
            page_id: pageIdVal,
            parent_id: overData.containerId as string,
            section_type: isContainer ? 'container' : comp.slug,
            variant_id: '',
            content: defContent as Record<string, unknown>,
            style: getDefaultStyle(isContainer ? 'container' : comp.slug, defContent as Record<string, unknown>),
            layout: isContainer ? { type: 'flex', direction: 'column' } : {},
            sort_order: 0,
            is_visible: true,
            custom_css: '',
            custom_id: '',
          }, overData.containerId as string);
          return;
        }

        // Dropped on canvas → if non-container, auto-wrap in a container
        const idx = rootIds.indexOf(overId);
        const insertPos = idx >= 0 ? idx + 1 : undefined;

        if (isContainer) {
          const isGrid = comp.slug === 'grid';
          addSection({
            id: generateSectionId(),
            page_id: pageIdVal,
            parent_id: null,
            section_type: 'container',
            variant_id: '',
            content: defContent as Record<string, unknown>,
            style: getDefaultStyle('container'),
            layout: isGrid
              ? { type: 'grid', columns: 'repeat(2, 1fr)' }
              : { type: 'flex', direction: 'column' },
            sort_order: 0,
            is_visible: true,
            custom_css: '',
            custom_id: '',
          }, null, insertPos);
        } else {
          const containerId = generateSectionId();
          addSection({
            id: containerId,
            page_id: pageIdVal,
            parent_id: null,
            section_type: 'container',
            variant_id: '',
            content: {},
            style: getDefaultStyle('container'),
            layout: { type: 'flex', direction: 'column' },
            sort_order: 0,
            is_visible: true,
            custom_css: '',
            custom_id: '',
          }, null, insertPos);

          addSection({
            id: generateSectionId(),
            page_id: pageIdVal,
            parent_id: containerId,
            section_type: comp.slug,
            variant_id: '',
            content: defContent as Record<string, unknown>,
            style: getDefaultStyle(comp.slug, defContent as Record<string, unknown>),
            layout: {},
            sort_order: 0,
            is_visible: true,
            custom_css: '',
            custom_id: '',
          }, containerId);
        }
        return;
      }

      // ── Existing section → container (move into) ──
      if (overData?.type === 'container') {
        const containerId = overData.containerId as string;
        if (String(active.id) !== containerId) {
          moveSection(String(active.id), containerId, 0);
        }
        return;
      }

      // ── Reorder within canvas ──
      if (active.id !== over.id && overId !== 'canvas-droppable') {
        const oldIdx = rootIds.indexOf(String(active.id));
        const newIdx = rootIds.indexOf(overId);
        if (oldIdx >= 0 && newIdx >= 0) {
          moveSection(String(active.id), null, newIdx);
        }
      }
    },
    [addSection, moveSection],
  );

  // Load page + sections directly by slug.
  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setError(null);

    apiGet<{ success: boolean; data: Page & { sections?: Section[] } }>(`/pages/${pageSlug}`)
      .then((res) => {
        if (cancelled) return;
        const pageData = res?.data ?? (res as unknown as Page & { sections?: Section[] });

        logger.info('PageBuilder', 'API response received', {
          pageId: pageData.id,
          title: pageData.title,
          sectionCount: pageData.sections?.length ?? 0,
        });

        setPage({
          id: pageData.id,
          title: pageData.title,
          slug: pageData.slug,
          status: pageData.status,
        });

        if (pageData.sections && Array.isArray(pageData.sections)) {
          const flat = flattenSections(pageData.sections);

          // Log section data types for debugging
          flat.forEach((s) => {
            Object.entries(s.content).forEach(([key, val]) => {
              if (val !== null && typeof val === 'object') {
                logger.warn('PageBuilder', `Section "${s.section_type}" (${s.id}) has object value in content.${key}`, {
                  type: Array.isArray(val) ? 'array' : 'object',
                  value: val,
                });
              }
            });
          });

          setSections(flat);
          // Load any Google Fonts used in sections
          loadAllUsedFonts(flat);
          // Snapshot for save diff after state settles
          setTimeout(() => snapshotOriginal(), 0);
        }
      })
      .catch((err: unknown) => {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : 'Failed to load page');
        }
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [pageSlug, setPage, setSections, setLoading, setError]);

  // Add full-screen builder class to body + apply saved theme
  useEffect(() => {
    document.body.classList.add('npb-builder-active');
    // Apply builder UI theme after DOM is ready
    requestAnimationFrame(() => {
      import('../utils/builderThemes').then(({ initBuilderTheme }) => initBuilderTheme());
    });
    return () => {
      document.body.classList.remove('npb-builder-active');
    };
  }, []);

  // Global keyboard shortcuts
  const handleKeyDown = useCallback(
    (e: KeyboardEvent) => {
      const isCtrl = e.ctrlKey || e.metaKey;
      const target = e.target as HTMLElement;

      // Don't capture when typing in inputs
      if (['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) return;
      if (target.isContentEditable) return;

      if (isCtrl && e.key === 's') {
        e.preventDefault();
        if (storePageId && !isSaving) {
          setSaving(true);
          savePageChanges(storePageId, pageSlug, sections, originalSections).then((result) => {
            if (result.success) {
              if (result.freshSections) {
                setSections(result.freshSections);
              }
              markSaved();
              snapshotOriginal();
              addToast('Page saved', 'success');
            } else {
              setSaving(false);
              addToast(result.error || 'Save failed', 'error');
            }
          });
        }
      }

      if (isCtrl && !e.shiftKey && e.key === 'z') {
        e.preventDefault();
        performUndo(sections, setSections);
      }

      if (isCtrl && e.shiftKey && (e.key === 'z' || e.key === 'Z')) {
        e.preventDefault();
        performRedo(sections, setSections);
      }

      if (isCtrl && (e.key === 'd' || e.key === 'D') && selectedSectionId) {
        e.preventDefault();
        duplicateSection(selectedSectionId);
      }

      if ((e.key === 'Delete' || e.key === 'Backspace') && selectedSectionId) {
        e.preventDefault();
        removeSection(selectedSectionId);
      }

      if (e.key === 'Escape') {
        selectSection(null);
      }
    },
    [sections, setSections, selectedSectionId, selectSection, removeSection, duplicateSection, addToast],
  );

  useEffect(() => {
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [handleKeyDown]);

  // Warn before leaving with unsaved changes
  useEffect(() => {
    const handleBeforeUnload = (e: BeforeUnloadEvent) => {
      if (isDirty) {
        e.preventDefault();
      }
    };
    window.addEventListener('beforeunload', handleBeforeUnload);
    return () => window.removeEventListener('beforeunload', handleBeforeUnload);
  }, [isDirty]);

  if (isLoading) {
    return (
      <div className="npb-builder">
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            height: '100%',
            color: '#6b7280',
          }}
        >
          Loading page builder...
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="npb-builder">
        <div
          style={{
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            justifyContent: 'center',
            height: '100%',
            color: '#ef4444',
            gap: 12,
          }}
        >
          <span style={{ fontSize: 16, fontWeight: 600 }}>Failed to load page</span>
          <span style={{ color: '#6b7280' }}>{error}</span>
        </div>
      </div>
    );
  }

  return (
    <DndContext
      sensors={dndSensors}
      collisionDetection={pointerWithin}
      onDragStart={handleDragStart}
      onDragOver={handleDragOver}
      onDragEnd={handleDragEnd}
    >
      <div className={`npb-builder ${dragActiveId ? 'npb-builder--dragging' : ''}`}>
        <ErrorBoundary name="Toolbar">
          <Toolbar />
        </ErrorBoundary>
        <div className="npb-panels">
          {!previewMode && (
            <ErrorBoundary name="LeftPanel">
              <LeftPanel />
            </ErrorBoundary>
          )}
          <ErrorBoundary name="Canvas">
            <Canvas />
          </ErrorBoundary>
        </div>
        <Toast />
        <Confirm />
      </div>

      <DragOverlay dropAnimation={null}>
        {dragActiveId ? (
          <div className="npb-drag-overlay">
            <div className="npb-drag-overlay__icon">⠿</div>
            <span>{dragLabel}</span>
          </div>
        ) : null}
      </DragOverlay>
    </DndContext>
  );
}
