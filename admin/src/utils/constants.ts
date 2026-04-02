import type { Breakpoint } from '../types/builder';

export const BREAKPOINTS: Record<Breakpoint, { label: string; icon: string; width: number }> = {
  desktop: { label: 'Desktop', icon: 'Monitor', width: 1024 },
  tablet: { label: 'Tablet', icon: 'Tablet', width: 768 },
  mobile: { label: 'Mobile', icon: 'Smartphone', width: 375 },
};

export const COMPONENT_CATEGORIES = [
  { slug: 'hero', label: 'Hero' },
  { slug: 'content', label: 'Content' },
  { slug: 'features', label: 'Features' },
  { slug: 'cta', label: 'Call to Action' },
  { slug: 'testimonials', label: 'Testimonials' },
  { slug: 'faq', label: 'FAQ' },
  { slug: 'contact', label: 'Contact' },
  { slug: 'gallery', label: 'Gallery' },
  { slug: 'pricing', label: 'Pricing' },
  { slug: 'team', label: 'Team' },
  { slug: 'stats', label: 'Statistics' },
  { slug: 'logo-cloud', label: 'Logo Cloud' },
  { slug: 'footer', label: 'Footer' },
  { slug: 'header', label: 'Header' },
  { slug: 'container', label: 'Container' },
  { slug: 'custom', label: 'Custom' },
] as const;

export const MAX_NESTING_DEPTH = 5;

/** Section types treated as layout containers — single source of truth. */
export const CONTAINER_TYPES = ['container'] as const;

/** Built-in section types that bypass DB component check. */
export const BUILTIN_SECTION_TYPES = ['container', 'heading', 'text_editor', 'image', 'button', 'spacer'] as const;
