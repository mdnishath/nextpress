import { useRef } from '@wordpress/element';
import { useDraggable } from '@dnd-kit/core';
import {
  Layout, Type, Star, MessageSquare, HelpCircle, Phone,
  Image, DollarSign, Users, BarChart3, Box, Grid3X3,
  Megaphone, Layers, MapPin, Newspaper, Award, Briefcase,
} from 'lucide-react';
import { useBuilderStore } from '../../store/builderStore';
import { generateSectionId } from '../../utils/helpers';
import type { Component, Section } from '../../types/builder';

const categoryIcons: Record<string, typeof Layout> = {
  hero: Layout, content: Type, features: Star, testimonials: MessageSquare,
  faq: HelpCircle, contact: Phone, gallery: Image, pricing: DollarSign,
  team: Users, stats: BarChart3, container: Box, structure: Box,
  cta: Megaphone, 'logo-cloud': Grid3X3, header: Layers, footer: Layers,
  about: Award, services: Briefcase, location: MapPin, blog: Newspaper,
  comparison: Layers, data: BarChart3, custom: Layers,
  basic: Type, heading: Type,
};

interface ComponentCardProps {
  component: Component;
}

export function ComponentCard({ component }: ComponentCardProps) {
  const { addSection, pageId } = useBuilderStore();
  const dragStartPos = useRef<{ x: number; y: number } | null>(null);

  const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
    id: `palette-${component.slug}`,
    data: {
      type: 'palette-component',
      componentSlug: component.slug,
      component,
    },
  });

  // Track mouse down position to distinguish click vs drag
  const handleMouseDown = (e: React.MouseEvent) => {
    dragStartPos.current = { x: e.clientX, y: e.clientY };
  };

  const handleMouseUp = (e: React.MouseEvent) => {
    if (!dragStartPos.current || !pageId) return;
    const dx = Math.abs(e.clientX - dragStartPos.current.x);
    const dy = Math.abs(e.clientY - dragStartPos.current.y);
    // Only treat as click if mouse barely moved (not a drag)
    if (dx < 5 && dy < 5) {
      addComponentToCanvas(component, pageId, addSection);
    }
    dragStartPos.current = null;
  };

  const Icon = categoryIcons[component.category] || categoryIcons[component.slug] || Layers;

  return (
    <div
      ref={setNodeRef}
      className={`npb-component-card ${isDragging ? 'npb-component-card--dragging' : ''}`}
      onMouseDown={handleMouseDown}
      onMouseUp={handleMouseUp}
      {...listeners}
      {...attributes}
    >
      <Icon size={20} />
      <span>{component.name}</span>
    </div>
  );
}

function addComponentToCanvas(
  component: Component,
  pageId: number,
  addSection: (section: Section, parentId?: string | null, position?: number) => void,
) {
  let defaultContent = component.default_content || {};
  if (typeof defaultContent === 'string') {
    try { defaultContent = JSON.parse(defaultContent); } catch { defaultContent = {}; }
  }

  const isContainer = component.is_container || component.slug === 'container';

  // When adding a non-container component to canvas root, wrap it in a container
  if (!isContainer) {
    const containerId = generateSectionId();
    addSection({
      id: containerId,
      page_id: pageId,
      parent_id: null,
      section_type: 'container',
      variant_id: '',
      content: {},
      style: {},
      layout: { type: 'flex', direction: 'column' },
      sort_order: 0,
      is_visible: true,
      custom_css: '',
      custom_id: '',
    });

    addSection({
      id: generateSectionId(),
      page_id: pageId,
      parent_id: containerId,
      section_type: component.slug,
      variant_id: '',
      content: defaultContent as Record<string, unknown>,
      style: {},
      layout: {},
      sort_order: 0,
      is_visible: true,
      custom_css: '',
      custom_id: '',
    }, containerId);
  } else {
    const isGrid = component.slug === 'grid';
    addSection({
      id: generateSectionId(),
      page_id: pageId,
      parent_id: null,
      section_type: 'container',
      variant_id: '',
      content: defaultContent as Record<string, unknown>,
      style: {},
      layout: isGrid
        ? { type: 'grid', columns: 'repeat(2, 1fr)' }
        : { type: 'flex', direction: 'column' },
      sort_order: 0,
      is_visible: true,
      custom_css: '',
      custom_id: '',
    });
  }
}
