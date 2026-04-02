import { useBuilderStore } from '../../store/builderStore';
import {
  DndContext,
  closestCenter,
  PointerSensor,
  useSensor,
  useSensors,
  type DragEndEvent,
} from '@dnd-kit/core';
import {
  SortableContext,
  useSortable,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
  ChevronRight,
  ChevronDown,
  Eye,
  EyeOff,
  Layers,
  Box,
  GripVertical,
} from 'lucide-react';
import { useState } from '@wordpress/element';
import type { Section } from '../../types/builder';

export function ElementNavigator() {
  const { getSectionTree, moveSection } = useBuilderStore();
  const tree = getSectionTree();

  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 5 } })
  );

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;
    if (!over || active.id === over.id) return;

    const allSections = useBuilderStore.getState().sections;
    const draggedSection = allSections.find((s) => s.id === active.id);
    const overSection = allSections.find((s) => s.id === over.id);
    if (!draggedSection || !overSection) return;

    // Move to the same parent as the target, at its position
    const targetParent = overSection.parent_id;
    const siblings = allSections
      .filter((s) => s.parent_id === targetParent)
      .sort((a, b) => a.sort_order - b.sort_order);
    const newIndex = siblings.findIndex((s) => s.id === over.id);

    moveSection(String(active.id), targetParent, newIndex >= 0 ? newIndex : 0);
  };

  if (tree.length === 0) {
    return (
      <div style={{ padding: 24, textAlign: 'center', color: '#a1a1aa', fontSize: 13 }}>
        No sections yet. Drag components to the canvas.
      </div>
    );
  }

  // Collect all section IDs for sortable context
  const allIds: string[] = [];
  function collectIds(sections: Section[]) {
    for (const s of sections) {
      allIds.push(s.id);
      if (s.children) collectIds(s.children);
    }
  }
  collectIds(tree);

  return (
    <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
      <SortableContext items={allIds} strategy={verticalListSortingStrategy}>
        <ul className="npb-navigator-tree">
          {tree.map((section) => (
            <TreeItem key={section.id} section={section} depth={0} />
          ))}
        </ul>
      </SortableContext>
    </DndContext>
  );
}

function TreeItem({ section, depth }: { section: Section; depth: number }) {
  const { selectedSectionId, selectSection, toggleSection } = useBuilderStore();
  const [expanded, setExpanded] = useState(true);

  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({ id: section.id });

  const style: React.CSSProperties = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.4 : 1,
  };

  const hasChildren = section.children && section.children.length > 0;
  const isSelected = selectedSectionId === section.id;

  return (
    <li ref={setNodeRef} style={style}>
      <div
        className={`npb-tree-item ${isSelected ? 'npb-tree-item--selected' : ''}`}
        onClick={() => selectSection(section.id)}
        style={{ paddingLeft: 8 + depth * 16 }}
      >
        {/* Drag handle */}
        <span
          className="npb-tree-item__drag"
          {...attributes}
          {...listeners}
          onClick={(e) => e.stopPropagation()}
          style={{ cursor: 'grab', color: '#6b7280', display: 'flex', alignItems: 'center' }}
        >
          <GripVertical size={12} />
        </span>

        {/* Expand/collapse toggle */}
        <span
          className="npb-tree-item__toggle"
          onClick={(e) => {
            e.stopPropagation();
            if (hasChildren) setExpanded(!expanded);
          }}
        >
          {hasChildren ? (
            expanded ? <ChevronDown size={12} /> : <ChevronRight size={12} />
          ) : null}
        </span>

        {/* Icon */}
        <span className="npb-tree-item__icon">
          {hasChildren ? <Box size={14} /> : <Layers size={14} />}
        </span>

        {/* Label */}
        <span className="npb-tree-item__label">
          {section.section_type.replace(/-/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())}
        </span>

        {/* Visibility toggle */}
        <span
          className="npb-tree-item__toggle"
          onClick={(e) => {
            e.stopPropagation();
            toggleSection(section.id);
          }}
          title={section.is_visible ? 'Hide' : 'Show'}
        >
          {section.is_visible ? <Eye size={12} /> : <EyeOff size={12} />}
        </span>
      </div>

      {/* Children */}
      {hasChildren && expanded && (
        <ul className="npb-navigator-tree" style={{ paddingLeft: 0 }}>
          {section.children!.map((child) => (
            <TreeItem key={child.id} section={child} depth={depth + 1} />
          ))}
        </ul>
      )}
    </li>
  );
}
