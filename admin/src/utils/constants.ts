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

/** Responsive default font sizes per heading tag */
const HEADING_SIZES: Record<string, { mobile: string; tablet: string; desktop: string }> = {
  h1: { mobile: '28px', tablet: '32px', desktop: '40px' },
  h2: { mobile: '22px', tablet: '26px', desktop: '32px' },
  h3: { mobile: '20px', tablet: '22px', desktop: '28px' },
  h4: { mobile: '18px', tablet: '20px', desktop: '24px' },
  h5: { mobile: '16px', tablet: '18px', desktop: '20px' },
  h6: { mobile: '14px', tablet: '16px', desktop: '16px' },
};

/**
 * Get default style for a component type.
 * Called when creating new sections to pre-fill style values.
 */
export function getDefaultStyle(sectionType: string, content?: Record<string, unknown>): Record<string, unknown> {
  switch (sectionType) {
    case 'container':
      return {
        paddingTop: '20px',
        paddingBottom: '20px',
        paddingLeft: '10px',
        paddingRight: '10px',
      };
    case 'heading': {
      const tag = (content?.tag as string) || 'h2';
      const sizes = HEADING_SIZES[tag] || HEADING_SIZES.h2;
      return {
        fontSize: sizes,
        fontWeight: '700',
        lineHeight: { mobile: '1.3em', tablet: '1.3em', desktop: '1.3em' },
      };
    }
    case 'text_editor':
      return {
        fontSize: { mobile: '14px', tablet: '15px', desktop: '16px' },
        lineHeight: { mobile: '1.6em', tablet: '1.6em', desktop: '1.6em' },
      };
    case 'button':
      return {
        fontSize: '16px',
        fontWeight: '600',
        paddingTop: '12px',
        paddingBottom: '12px',
        paddingLeft: '24px',
        paddingRight: '24px',
        backgroundColor: '#7c3aed',
        textColor: '#ffffff',
        borderRadius: '6px',
      };
    case 'spacer':
      return {};
    case 'image':
      return {};
    default:
      return {};
  }
}
