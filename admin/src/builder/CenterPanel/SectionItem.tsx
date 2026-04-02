import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
  GripVertical,
  Copy,
  Trash2,
  Eye,
  EyeOff,
} from 'lucide-react';
import { useBuilderStore } from '../../store/builderStore';
import { useUIStore } from '../../store/uiStore';
import { ContainerItem } from './ContainerItem';
import { SectionStyleTag } from './SectionStyleTag';
import type { Section } from '../../types/builder';

import { CONTAINER_TYPES } from '../../utils/constants';

interface SectionItemProps {
  section: Section;
}

export function SectionItem({ section }: SectionItemProps) {
  const {
    selectedSectionId,
    hoveredSectionId,
    selectSection,
    hoverSection,
    removeSection,
    duplicateSection,
    toggleSection,
  } = useBuilderStore();

  const { showConfirm, previewMode } = useUIStore();

  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({ id: section.id });

  // Only dnd-kit transform/transition/opacity — visual styles come from CSS class
  const style: React.CSSProperties = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.5 : 1,
  };

  const isSelected = selectedSectionId === section.id;
  const isHovered = hoveredSectionId === section.id;

  const handleDelete = (e: React.MouseEvent) => {
    e.stopPropagation();
    showConfirm('Delete Section', 'Are you sure you want to delete this section?', () => {
      removeSection(section.id);
    });
  };

  const handleDuplicate = (e: React.MouseEvent) => {
    e.stopPropagation();
    duplicateSection(section.id);
  };

  const handleToggle = (e: React.MouseEvent) => {
    e.stopPropagation();
    toggleSection(section.id);
  };

  const sectionLabel = section.section_type
    .replace(/-/g, ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());

  const extraClasses = (section.style.cssClasses as string) || '';

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={[
        'npb-section-item',
        `np-section-${section.id}`,
        isSelected && 'npb-section-item--selected',
        isHovered && 'npb-section-item--hovered',
        !section.is_visible && 'npb-section-item--hidden',
        extraClasses,
      ]
        .filter(Boolean)
        .join(' ')}
      id={section.custom_id || undefined}
      onClick={(e) => {
        e.stopPropagation();
        selectSection(section.id);
      }}
      onMouseEnter={() => hoverSection(section.id)}
      onMouseLeave={() => hoverSection(null)}
    >
      {/* Inject generated CSS */}
      <SectionStyleTag section={section} />

      {/* Section label + controls (hidden in preview mode) */}
      {!previewMode && (
        <>
          <div className="npb-section-item__label">{sectionLabel}</div>
          <div className="npb-section-item__controls">
            <button
              className="npb-section-control-btn"
              {...listeners}
              {...attributes}
              title="Drag to reorder"
            >
              <GripVertical size={14} />
            </button>
            <button className="npb-section-control-btn" onClick={handleToggle} title={section.is_visible ? 'Hide' : 'Show'}>
              {section.is_visible ? <Eye size={14} /> : <EyeOff size={14} />}
            </button>
            <button className="npb-section-control-btn" onClick={handleDuplicate} title="Duplicate">
              <Copy size={14} />
            </button>
            <button
              className="npb-section-control-btn npb-section-control-btn--danger"
              onClick={handleDelete}
              title="Delete"
            >
              <Trash2 size={14} />
            </button>
          </div>
        </>
      )}

      {/* Section content: container with children or content preview */}
      <div className="npb-section-item__content">
        {CONTAINER_TYPES.includes(section.section_type) ? (
          <ContainerItem section={section} />
        ) : (
          <SectionPreview section={section} />
        )}
      </div>
    </div>
  );
}

/**
 * Section content preview on canvas — renders live WYSIWYG for known types.
 */
function str(val: unknown): string {
  if (typeof val === 'string') return val;
  if (typeof val === 'number' || typeof val === 'boolean') return String(val);
  return '';
}

const TAG_SIZES: Record<string, number> = { h1: 32, h2: 26, h3: 22, h4: 18, h5: 16, h6: 14 };

function SectionPreview({ section }: { section: Section }) {
  const c = section.content;

  // ── Heading — all styling comes from CSS (SectionStyleTag), just render text ──
  if (section.section_type === 'heading') {
    const text = str(c.text) || 'Add Your Heading Text Here';
    const tag = str(c.tag) || 'h2';
    const hasCustomSize = !!section.style.fontSize;
    const fallbackSize = TAG_SIZES[tag] || 20;

    return (
      <div style={{
        padding: '8px 0',
        // Only set fallback styles when no custom CSS values exist
        fontSize: hasCustomSize ? undefined : fallbackSize,
        fontWeight: section.style.fontWeight ? undefined : 700,
        lineHeight: section.style.lineHeight ? undefined : 1.3,
        // textAlign comes from style.textAlign via CSS generator — no inline override
      }}>
        {text}
      </div>
    );
  }

  // ── Text Editor — no inline styles, CSS generator handles everything ──
  if (section.section_type === 'text_editor') {
    const html = str(c.content) || '<p>Add your text here. Click to edit.</p>';
    return (
      <div dangerouslySetInnerHTML={{ __html: html }} />
    );
  }

  // ── Image ──
  if (section.section_type === 'image') {
    const src = str(c.src);
    const alt = str(c.alt) || 'Image';
    const caption = str(c.caption);
    const alignment = str(c.alignment) || 'center';
    return (
      <div style={{ padding: '8px 0', textAlign: alignment as 'left' | 'center' | 'right' }}>
        {src ? (
          <>
            <img src={src} alt={alt} style={{ maxWidth: '100%', height: 'auto', borderRadius: 4 }} />
            {caption && <p style={{ fontSize: 12, color: '#6b7280', marginTop: 4 }}>{caption}</p>}
          </>
        ) : (
          <div style={{
            width: '100%', height: 200, background: '#f3f4f6', borderRadius: 8,
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            border: '2px dashed #d1d5db', color: '#9ca3af', fontSize: 13,
          }}>
            Click to add image
          </div>
        )}
      </div>
    );
  }

  // ── Button — minimal inline, rest from CSS generator ──
  if (section.section_type === 'button') {
    const text = str(c.text) || 'Click Here';
    const alignment = str(c.alignment) || 'left';
    const size = str(c.size) || 'medium';
    const sizePad = size === 'small' ? '8px 16px' : size === 'large' ? '16px 32px' : '12px 24px';
    const sizeFontFallback = size === 'small' ? 13 : size === 'large' ? 16 : 14;
    const hasCustomBg = !!section.style.backgroundColor;
    const hasCustomColor = !!section.style.textColor;
    const hasCustomSize = !!section.style.fontSize;
    const hasCustomPad = !!section.style.paddingTop;
    return (
      <div style={{ textAlign: alignment as 'left' | 'center' | 'right' }}>
        <span style={{
          display: 'inline-block',
          padding: hasCustomPad ? undefined : sizePad,
          fontSize: hasCustomSize ? undefined : sizeFontFallback,
          fontWeight: 600,
          background: hasCustomBg ? undefined : '#7c3aed',
          color: hasCustomColor ? undefined : '#fff',
          borderRadius: 6,
          cursor: 'default', lineHeight: 1.4,
        }}>
          {text}
        </span>
      </div>
    );
  }

  // ── Spacer ──
  if (section.section_type === 'spacer') {
    const height = Number(c.height) || 40;
    return (
      <div style={{
        height, display: 'flex', alignItems: 'center', justifyContent: 'center',
        border: '1px dashed #e5e7eb', borderRadius: 4, color: '#d1d5db', fontSize: 11,
      }}>
        {height}px
      </div>
    );
  }

  // ── Default preview ──
  const heading = str(c.heading || c.title || c.headline || c.text);
  const subheading = str(c.subheading || c.subtitle || c.description);

  return (
    <div style={{ minHeight: 40, padding: '8px 0' }}>
      {heading && (
        <div style={{ fontSize: 16, fontWeight: 600, marginBottom: 4, color: '#09090b' }}>
          {heading}
        </div>
      )}
      {subheading && (
        <div style={{ fontSize: 13, color: '#6b7280', lineHeight: 1.4 }}>
          {subheading.length > 120 ? subheading.slice(0, 120) + '...' : subheading}
        </div>
      )}
      {!heading && !subheading && (
        <div style={{ fontSize: 13, color: '#a1a1aa', fontStyle: 'italic' }}>
          {section.section_type.replace(/-/g, ' ')} widget
        </div>
      )}
    </div>
  );
}
