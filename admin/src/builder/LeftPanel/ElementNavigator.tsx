import { useBuilderStore } from '../../store/builderStore';
import {
  ChevronRight,
  ChevronDown,
  Eye,
  EyeOff,
  Layers,
  Box,
} from 'lucide-react';
import { useState } from '@wordpress/element';
import type { Section } from '../../types/builder';

export function ElementNavigator() {
  const { getSectionTree, selectedSectionId, selectSection } = useBuilderStore();
  const tree = getSectionTree();

  if (tree.length === 0) {
    return (
      <div style={{ padding: 24, textAlign: 'center', color: '#a1a1aa', fontSize: 13 }}>
        No sections yet. Drag components to the canvas.
      </div>
    );
  }

  return (
    <ul className="npb-navigator-tree">
      {tree.map((section) => (
        <TreeItem key={section.id} section={section} depth={0} />
      ))}
    </ul>
  );
}

function TreeItem({ section, depth }: { section: Section; depth: number }) {
  const { selectedSectionId, selectSection, toggleSection } = useBuilderStore();
  const [expanded, setExpanded] = useState(true);

  const hasChildren = section.children && section.children.length > 0;
  const isSelected = selectedSectionId === section.id;

  return (
    <li>
      <div
        className={`npb-tree-item ${isSelected ? 'npb-tree-item--selected' : ''}`}
        onClick={() => selectSection(section.id)}
        style={{ paddingLeft: 8 + depth * 16 }}
      >
        {/* Expand/collapse toggle */}
        <span
          className="npb-tree-item__toggle"
          onClick={(e) => {
            e.stopPropagation();
            if (hasChildren) setExpanded(!expanded);
          }}
        >
          {hasChildren ? (
            expanded ? (
              <ChevronDown size={12} />
            ) : (
              <ChevronRight size={12} />
            )
          ) : null}
        </span>

        {/* Icon */}
        <span className="npb-tree-item__icon">
          {section.children && section.children.length > 0 ? (
            <Box size={14} />
          ) : (
            <Layers size={14} />
          )}
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
