import { useDroppable } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { useBuilderStore } from '../../store/builderStore';
import { SectionItem } from './SectionItem';
import { SectionStyleTag } from './SectionStyleTag';
import { Plus } from 'lucide-react';
import { generateSectionId } from '../../utils/helpers';
import { ErrorBoundary } from '../../components/ErrorBoundary';
import type { Section, ContainerLayout } from '../../types/builder';

interface ContainerItemProps {
  section: Section;
}

/**
 * 12 structure presets — Elementor-style visual column layouts.
 * Each preset defines: columns, fr template, and visual ratios.
 */
const STRUCTURE_PRESETS = [
  { id: '1col',     cols: 1, template: '1fr',               visual: [1] },
  { id: '2eq',      cols: 2, template: '1fr 1fr',           visual: [1, 1] },
  { id: '2-l',      cols: 2, template: '2fr 1fr',           visual: [2, 1] },
  { id: '2-r',      cols: 2, template: '1fr 2fr',           visual: [1, 2] },
  { id: '3eq',      cols: 3, template: '1fr 1fr 1fr',       visual: [1, 1, 1] },
  { id: '3-l',      cols: 3, template: '2fr 1fr 1fr',       visual: [2, 1, 1] },
  { id: '3-r',      cols: 3, template: '1fr 1fr 2fr',       visual: [1, 1, 2] },
  { id: '3-m',      cols: 3, template: '1fr 2fr 1fr',       visual: [1, 2, 1] },
  { id: '4eq',      cols: 4, template: '1fr 1fr 1fr 1fr',   visual: [1, 1, 1, 1] },
  { id: '2-1/3',    cols: 2, template: '1fr 2fr',           visual: [1, 2] },
  { id: '2-1/4',    cols: 2, template: '1fr 3fr',           visual: [1, 3] },
  { id: '2-3/4',    cols: 2, template: '3fr 1fr',           visual: [3, 1] },
];

export function ContainerItem({ section }: ContainerItemProps) {
  const { getChildren, addSection, updateLayout, pageId } = useBuilderStore();
  const isNested = section.parent_id !== null;
  const children = getChildren(section.id);
  const childIds = children.map((c) => c.id);

  const { setNodeRef, isOver } = useDroppable({
    id: `container-${section.id}`,
    data: { type: 'container', containerId: section.id },
  });

  const handleAddChild = (e?: React.MouseEvent) => {
    if (e) e.stopPropagation();
    if (!pageId) return;
    addSection({
      id: generateSectionId(),
      page_id: pageId,
      parent_id: section.id,
      section_type: 'container',
      variant_id: '',
      content: {},
      style: {
        paddingTop: '20px',
        paddingBottom: '20px',
        paddingLeft: '10px',
        paddingRight: '10px',
      },
      layout: { type: 'flex', direction: 'column' },
      sort_order: children.length,
      is_visible: true,
      custom_css: '',
      custom_id: '',
    }, section.id);
  };

  /** Apply a structure preset — sets grid layout + creates child containers */
  const handlePreset = (preset: typeof STRUCTURE_PRESETS[0], e: React.MouseEvent) => {
    e.stopPropagation();
    if (!pageId) return;

    // Set parent layout
    if (preset.cols === 1) {
      updateLayout(section.id, {
        type: 'flex',
        direction: 'column',
        gap: '0px',
      } as Partial<ContainerLayout>);
    } else {
      updateLayout(section.id, {
        type: 'flex',
        direction: 'row',
        gap: '0px',
      } as Partial<ContainerLayout>);
    }

    // Create child containers if empty
    if (children.length === 0) {
      for (let i = 0; i < preset.cols; i++) {
        const flexVal = preset.visual[i];
        addSection({
          id: generateSectionId(),
          page_id: pageId,
          parent_id: section.id,
          section_type: 'container',
          variant_id: '',
          content: {},
          style: {
            flexSize: 'custom',
            flexGrow: String(flexVal),
            flexShrink: '1',
            flexBasis: '0%',
          },
          layout: { type: 'flex', direction: 'column' },
          sort_order: i,
          is_visible: true,
          custom_css: '',
          custom_id: '',
        }, section.id);
      }
    }
  };

  return (
    <div
      ref={setNodeRef}
      className={`np-inner-${section.id} npb-container${children.length === 0 ? ' npb-container--empty' : ''}`}
      style={{
        minHeight: children.length > 0 ? undefined : 80,
        outline: isOver ? '2px dashed var(--npb-drop-indicator)' : undefined,
        transition: 'outline 0.15s',
      }}
    >
      <SectionStyleTag section={section} />

      {children.length > 0 ? (
        <SortableContext items={childIds} strategy={verticalListSortingStrategy}>
          {children.map((child) => (
            <ErrorBoundary key={child.id} name={`SectionItem[${child.section_type}:${child.id}]`}>
              <SectionItem section={child} />
            </ErrorBoundary>
          ))}
        </SortableContext>
      ) : isNested ? (
        /* Nested empty child: just a clean + button (Elementor-style) */
        <div style={{
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          padding: '20px 8px', minHeight: 60,
        }}>
          <button
            onClick={handleAddChild}
            className="npb-add-btn"
            title="Add element"
            type="button"
          >
            <Plus size={16} />
          </button>
        </div>
      ) : (
        /* Root empty container: structure picker */
        <div style={{
          display: 'flex', flexDirection: 'column', alignItems: 'center',
          justifyContent: 'center', padding: '24px 16px', gap: 16,
        }}>
          <button
            onClick={handleAddChild}
            className="npb-add-btn"
            title="Add container"
            type="button"
          >
            <Plus size={20} />
          </button>

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 6 }}>
            {STRUCTURE_PRESETS.map((preset) => (
              <button
                key={preset.id}
                onClick={(e) => handlePreset(preset, e)}
                title={preset.template}
                className="npb-preset-btn"
                type="button"
              >
                {preset.visual.map((fr, i) => (
                  <div key={i} style={{ flex: fr, background: '#d1d5db', borderRadius: 2, minHeight: '100%' }} />
                ))}
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
