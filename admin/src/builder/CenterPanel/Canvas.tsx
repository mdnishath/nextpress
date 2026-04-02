import { useCallback } from '@wordpress/element';
import { useDroppable } from '@dnd-kit/core';
import {
  SortableContext,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { useBuilderStore } from '../../store/builderStore';
import { useUIStore } from '../../store/uiStore';
import { SectionItem } from './SectionItem';
import { EmptyCanvas } from './EmptyCanvas';
import { AddSectionButton } from './AddSectionButton';
import { ErrorBoundary } from '../../components/ErrorBoundary';

/**
 * Center canvas panel — droppable target for sections.
 * DndContext lives in PageBuilder (parent), so drag from LeftPanel works.
 */
export function Canvas() {
  const { breakpoint, selectSection, getRootSections } = useBuilderStore();
  const { previewMode } = useUIStore();

  const rootSections = getRootSections();
  const sectionIds = rootSections.map((s) => s.id);

  const { setNodeRef, isOver } = useDroppable({ id: 'canvas-droppable' });

  const handleCanvasClick = useCallback(
    (e: React.MouseEvent) => {
      if ((e.target as HTMLElement).classList.contains('npb-canvas')) {
        selectSection(null);
      }
    },
    [selectSection],
  );

  const canvasClass = `npb-canvas ${breakpoint !== 'desktop' ? `npb-canvas--${breakpoint}` : ''}`;

  return (
    <div className="npb-canvas-wrapper">
      <div
        ref={setNodeRef}
        className={canvasClass}
        onClick={handleCanvasClick}
        style={{
          border: isOver ? '2px dashed #3b82f6' : '2px dashed transparent',
          borderRadius: 12,
          background: isOver ? 'rgba(59, 130, 246, 0.02)' : undefined,
          transition: 'border-color 0.2s, background 0.2s',
        }}
      >
        {rootSections.length === 0 ? (
          <EmptyCanvas />
        ) : (
          <SortableContext items={sectionIds} strategy={verticalListSortingStrategy}>
            {rootSections.map((section) => (
              <ErrorBoundary key={section.id} name={`SectionItem[${section.section_type}:${section.id}]`}>
                <SectionItem section={section} />
              </ErrorBoundary>
            ))}
          </SortableContext>
        )}

        {!previewMode && <AddSectionButton />}
      </div>
    </div>
  );
}
