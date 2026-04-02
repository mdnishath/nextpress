/**
 * Core data types for the NextPress page builder.
 * Mobile-first responsive architecture.
 */

// ─── Responsive Value ───────────────────────────────────────────────

/** A value that can differ per breakpoint. Mobile is the base (required). */
export interface ResponsiveValue {
  mobile: string;
  tablet?: string;
  desktop?: string;
}

/** A style property that can be a plain string (same on all breakpoints) or responsive. */
export type ResponsiveString = string | ResponsiveValue;

/** Type guard: check if a value is a responsive object vs a plain string. */
export function isResponsiveValue(val: unknown): val is ResponsiveValue {
  return (
    typeof val === 'object' &&
    val !== null &&
    'mobile' in val &&
    typeof (val as ResponsiveValue).mobile === 'string'
  );
}

// ─── Breakpoints ────────────────────────────────────────────────────

export type Breakpoint = 'desktop' | 'tablet' | 'mobile';

// ─── Page ───────────────────────────────────────────────────────────

export interface Page {
  id: number;
  title: string;
  slug: string;
  status: 'draft' | 'published';
  template: string;
  seo_title: string;
  seo_description: string;
  created_at: string;
  updated_at: string;
}

// ─── Section ────────────────────────────────────────────────────────

export interface Section {
  id: string;
  page_id: number;
  parent_id: string | null;
  section_type: string;
  variant_id: string;
  content: Record<string, unknown>;
  style: SectionStyle;
  layout: ContainerLayout;
  sort_order: number;
  is_visible: boolean;
  custom_css: string;
  custom_id: string;
  children?: Section[];

  /** Show/hide per breakpoint. True = visible. Defaults to all true. */
  responsiveVisibility?: {
    desktop: boolean;
    tablet: boolean;
    mobile: boolean;
  };
}

// ─── Section Style ──────────────────────────────────────────────────

export interface SectionStyle {
  // Spacing (responsive)
  paddingTop?: ResponsiveString;
  paddingRight?: ResponsiveString;
  paddingBottom?: ResponsiveString;
  paddingLeft?: ResponsiveString;
  marginTop?: ResponsiveString;
  marginRight?: ResponsiveString;
  marginBottom?: ResponsiveString;
  marginLeft?: ResponsiveString;

  // Background
  backgroundColor?: string;
  backgroundGradient?: string;
  backgroundImage?: string;
  backgroundSize?: string;
  backgroundPosition?: string;
  backgroundRepeat?: string;
  backgroundOverlayColor?: string;
  backgroundOverlayOpacity?: number;

  // Text color
  textColor?: string;

  // Border (responsive radius/width)
  borderWidth?: ResponsiveString;
  borderStyle?: string;
  borderColor?: string;
  borderRadius?: ResponsiveString;
  boxShadow?: string;

  // Typography (responsive size/height/spacing)
  fontFamily?: string;
  fontSize?: ResponsiveString;
  fontWeight?: string;
  lineHeight?: ResponsiveString;
  letterSpacing?: ResponsiveString;
  textTransform?: string;
  textAlign?: string;

  // Size (responsive)
  width?: ResponsiveString;
  maxWidth?: ResponsiveString;
  minHeight?: ResponsiveString;
  overflow?: string;

  // Flex child
  alignSelf?: ResponsiveString;
  order?: string;
  flexSize?: string; // none | grow | shrink | custom
  flexGrow?: string;
  flexShrink?: string;
  flexBasis?: string;

  // Position
  position?: string; // relative | absolute | fixed | sticky

  // Hover state
  hoverBackgroundColor?: string;
  hoverBackgroundGradient?: string;
  hoverBackgroundImage?: string;
  hoverBorderColor?: string;

  // Advanced
  zIndex?: string;
  cssClasses?: string;

  /** Allow arbitrary keys for forward-compat */
  [key: string]: unknown;
}

// ─── Container Layout ───────────────────────────────────────────────

export type HtmlTag = 'div' | 'section' | 'article' | 'aside' | 'header' | 'footer' | 'main' | 'nav';

export interface ContainerLayout {
  type?: 'flex' | 'grid';
  direction?: 'row' | 'row-reverse' | 'column' | 'column-reverse';
  wrap?: 'nowrap' | 'wrap';
  justifyContent?: string;
  alignItems?: string;
  gap?: ResponsiveString;
  columnGap?: ResponsiveString;
  rowGap?: ResponsiveString;
  columns?: string;
  rows?: string;
  width?: ResponsiveString;
  maxWidth?: ResponsiveString;
  contentWidth?: 'boxed' | 'full-width';
  htmlTag?: HtmlTag;
  padding?: { top: string; right: string; bottom: string; left: string };

  /** Allow arbitrary keys for forward-compat */
  [key: string]: unknown;
}

// ─── Component ──────────────────────────────────────────────────────

export interface Component {
  id: number;
  slug: string;
  name: string;
  description: string;
  category: string;
  icon: string;
  content_schema: ContentSchema;
  default_content: Record<string, unknown>;
  is_container: boolean;
  is_active?: boolean | number;
  is_user_created?: boolean | number;
}

export interface ContentSchema {
  fields: ContentField[];
}

export interface ContentField {
  key: string;
  label: string;
  type: FieldType;
  default?: unknown;
  options?: { label: string; value: string }[];
  placeholder?: string;
  min?: number;
  max?: number;
  step?: number;
  fields?: ContentField[]; // For repeater
}

export type FieldType =
  | 'text'
  | 'textarea'
  | 'richtext'
  | 'number'
  | 'range'
  | 'select'
  | 'boolean'
  | 'color'
  | 'image'
  | 'url'
  | 'icon'
  | 'button_preset'
  | 'form_select'
  | 'repeater'
  | 'responsive';

// ─── Variant ────────────────────────────────────────────────────────

export interface Variant {
  id: string;
  component_id: number;
  name: string;
  slug: string;
  description: string;
  thumbnail: string;
  default_style: SectionStyle;
}

// ─── Theme ──────────────────────────────────────────────────────────

export interface Theme {
  id: number;
  name: string;
  slug: string;
  colors: Record<string, string>;
  typography: Record<string, unknown>;
  is_active: boolean;
}

// ─── Button Preset ──────────────────────────────────────────────────

export interface ButtonPreset {
  id: number;
  name: string;
  slug: string;
  style: Record<string, string>;
}
