/**
 * CSS Generator — converts Section data into CSS strings with media queries.
 * Mobile-first: base styles = mobile, @media overrides for tablet/desktop.
 * Used in both the builder preview and the Next.js frontend.
 */

import type { Section, SectionStyle, ContainerLayout, ResponsiveString, Breakpoint } from '../types/builder';
import { isResponsiveValue } from '../types/builder';
import { getValueForBreakpoint } from './responsive';

// ─── Breakpoint Thresholds ──────────────────────────────────────────

const TABLET_MIN = 768;
const DESKTOP_MIN = 1025;

// ─── Helpers ────────────────────────────────────────────────────────

/** Convert camelCase to kebab-case: paddingTop → padding-top */
function toKebab(key: string): string {
  return key.replace(/[A-Z]/g, (m) => '-' + m.toLowerCase());
}

/** Extract mobile/tablet/desktop values from a ResponsiveString. */
function resolveResponsive(val: ResponsiveString | undefined): {
  mobile: string;
  tablet: string | undefined;
  desktop: string | undefined;
} {
  if (val === undefined || val === null) return { mobile: '', tablet: undefined, desktop: undefined };
  if (typeof val === 'string') return { mobile: val, tablet: undefined, desktop: undefined };
  if (isResponsiveValue(val)) {
    return {
      mobile: val.mobile || '',
      tablet: val.tablet && val.tablet !== val.mobile ? val.tablet : undefined,
      desktop: val.desktop && val.desktop !== (val.tablet ?? val.mobile) ? val.desktop : undefined,
    };
  }
  return { mobile: '', tablet: undefined, desktop: undefined };
}

/** Push a CSS declaration into the appropriate bucket. */
function pushDecl(
  val: ResponsiveString | undefined,
  cssProp: string,
  base: string[],
  tablet: string[],
  desktop: string[],
) {
  const { mobile, tablet: t, desktop: d } = resolveResponsive(val);
  if (mobile) base.push(`  ${cssProp}: ${mobile};`);
  if (t) tablet.push(`  ${cssProp}: ${t};`);
  if (d) desktop.push(`  ${cssProp}: ${d};`);
}

// ─── Style → CSS Mapping ────────────────────────────────────────────

/** Map of SectionStyle keys → CSS property names (only keys that differ from simple kebab). */
const STYLE_KEY_MAP: Record<string, string> = {
  textColor: 'color',
  backgroundGradient: 'background',
};

/** Style keys that are NOT directly mapped to CSS. */
const SKIP_STYLE_KEYS = new Set([
  'backgroundOverlayColor', 'backgroundOverlayOpacity',
  'zIndex', 'cssClasses',
  // Flex child (handled by flexSize switch)
  'flexSize', 'flexGrow', 'flexShrink', 'flexBasis',
  // Hover state keys (handled separately)
  'hoverBackgroundColor', 'hoverBackgroundGradient', 'hoverBackgroundImage', 'hoverBorderColor',
]);

/**
 * ALL style properties are potentially responsive.
 * Any value stored as ResponsiveValue {mobile, tablet?, desktop?} gets @media queries.
 * Plain strings go to base CSS only (backwards compatible).
 * This set lists keys that should ALWAYS use pushDecl (even if stored as plain string).
 */
const ALWAYS_RESPONSIVE_KEYS = new Set([
  'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
  'marginTop', 'marginRight', 'marginBottom', 'marginLeft',
  'fontSize', 'lineHeight', 'letterSpacing',
  'width', 'maxWidth', 'minHeight',
  'borderWidth', 'borderRadius',
  'alignSelf',
]);

// ─── Public API ─────────────────────────────────────────────────────

export function buildSelector(section: Section): string {
  return `.np-section-${section.id}`;
}

/**
 * Generate a complete CSS string for a section including media queries.
 */
export function generateSectionCSS(section: Section): string {
  const selector = buildSelector(section);
  const base: string[] = [];
  const tablet: string[] = [];
  const desktop: string[] = [];

  // ── Style properties ──
  const style = (section.style || {}) as SectionStyle;

  for (const [key, val] of Object.entries(style)) {
    if (val === undefined || val === null || val === '') continue;
    if (SKIP_STYLE_KEYS.has(key)) continue;

    const cssProp = STYLE_KEY_MAP[key] || toKebab(key);

    // Special handling: backgroundImage needs url() wrapper, fontFamily needs quotes
    if (key === 'backgroundImage') {
      const imgVal = isResponsiveValue(val) ? val : { mobile: val as string };
      const wrap = (v: string) => v ? `url(${v})` : '';
      const rv = imgVal as { mobile: string; tablet?: string; desktop?: string };
      if (rv.mobile) base.push(`  background-image: ${wrap(rv.mobile)};`);
      if (rv.tablet && rv.tablet !== rv.mobile) tablet.push(`  background-image: ${wrap(rv.tablet)};`);
      if (rv.desktop && rv.desktop !== (rv.tablet ?? rv.mobile)) desktop.push(`  background-image: ${wrap(rv.desktop)};`);
      continue;
    }
    if (key === 'fontFamily') {
      const fv = isResponsiveValue(val) ? val : { mobile: val as string };
      const quote = (v: string) => v ? (v.includes(' ') ? `"${v}", sans-serif` : `${v}, sans-serif`) : '';
      const rv = fv as { mobile: string; tablet?: string; desktop?: string };
      if (rv.mobile) base.push(`  font-family: ${quote(rv.mobile)};`);
      if (rv.tablet && rv.tablet !== rv.mobile) tablet.push(`  font-family: ${quote(rv.tablet)};`);
      if (rv.desktop && rv.desktop !== (rv.tablet ?? rv.mobile)) desktop.push(`  font-family: ${quote(rv.desktop)};`);
      continue;
    }

    // All other properties: auto-detect responsive
    if (isResponsiveValue(val) || ALWAYS_RESPONSIVE_KEYS.has(key)) {
      pushDecl(val as ResponsiveString, cssProp, base, tablet, desktop);
    } else if (typeof val === 'string') {
      base.push(`  ${cssProp}: ${val};`);
    } else if (typeof val === 'number') {
      base.push(`  ${cssProp}: ${val};`);
    }
  }

  // Background defaults when image is set
  if (style.backgroundImage) {
    if (!style.backgroundSize) base.push('  background-size: cover;');
    if (!style.backgroundPosition) base.push('  background-position: center;');
    if (!style.backgroundRepeat) base.push('  background-repeat: no-repeat;');
  }

  // z-index
  if (style.zIndex) {
    base.push(`  z-index: ${style.zIndex};`);
  }

  // Position
  if (style.position) {
    base.push(`  position: ${style.position};`);
  }

  // ── Flex child properties ──
  if (style.flexSize) {
    switch (style.flexSize) {
      case 'none':
        base.push('  flex: 0 0 auto;');
        break;
      case 'grow':
        base.push('  flex: 1 0 0%;');
        break;
      case 'shrink':
        base.push('  flex: 0 1 auto;');
        break;
      case 'custom':
        if (style.flexGrow) base.push(`  flex-grow: ${style.flexGrow};`);
        if (style.flexShrink) base.push(`  flex-shrink: ${style.flexShrink};`);
        if (style.flexBasis) base.push(`  flex-basis: ${style.flexBasis};`);
        break;
    }
  }

  // Order
  if (style.order) {
    base.push(`  order: ${style.order};`);
  }

  // ── Container: sizing on wrapper (.np-section), layout on inner (.np-inner) ──
  if (section.section_type === 'container') {
    const layout = (section.layout || {}) as ContainerLayout;

    // Content width goes on the WRAPPER (SectionItem)
    base.push('  width: 100%;');
    if (layout.contentWidth === 'boxed' && layout.maxWidth) {
      pushDecl(layout.maxWidth, 'max-width', base, tablet, desktop);
      base.push('  margin-left: auto;');
      base.push('  margin-right: auto;');
    }

    if (layout.width) {
      pushDecl(layout.width, 'width', base, tablet, desktop);
    }
  }

  // ── Background overlay — ::before pseudo-element ──
  const overlayColor = getValueForBreakpoint(style.backgroundOverlayColor as ResponsiveString, 'mobile');
  if (overlayColor) {
    const opacity = parseInt(String(getValueForBreakpoint(style.backgroundOverlayOpacity as ResponsiveString, 'mobile') || '50')) / 100;
    base.push('  position: relative;');
    // Overlay pseudo-element added after main selector
  }

  // ── Responsive visibility ──
  const vis = section.responsiveVisibility;
  if (vis) {
    if (!vis.mobile) {
      base.push('  display: none !important;');
      if (vis.tablet) tablet.push(`  display: ${section.section_type === 'container' ? ((section.layout || {}).type || 'flex') : 'block'} !important;`);
    }
    if (vis.mobile && !vis.tablet) {
      tablet.push('  display: none !important;');
      if (vis.desktop) desktop.push(`  display: ${section.section_type === 'container' ? ((section.layout || {}).type || 'flex') : 'block'} !important;`);
    }
    if (vis.mobile && vis.tablet && !vis.desktop) {
      desktop.push('  display: none !important;');
    }
  }

  // ── Assemble CSS string ──
  const parts: string[] = [];

  if (base.length > 0) {
    parts.push(`${selector} {\n${base.join('\n')}\n}`);
  }

  if (tablet.length > 0) {
    parts.push(`@media (min-width: ${TABLET_MIN}px) {\n  ${selector} {\n  ${tablet.join('\n  ')}\n  }\n}`);
  }

  if (desktop.length > 0) {
    parts.push(`@media (min-width: ${DESKTOP_MIN}px) {\n  ${selector} {\n  ${desktop.join('\n  ')}\n  }\n}`);
  }

  // ── Background overlay ::before pseudo-element ──
  if (overlayColor) {
    const opacity = parseInt(String(getValueForBreakpoint(style.backgroundOverlayOpacity as ResponsiveString, 'mobile') || '50')) / 100;
    parts.push(`${selector}::before {\n  content: "";\n  position: absolute;\n  top: 0; right: 0; bottom: 0; left: 0;\n  background-color: ${overlayColor};\n  opacity: ${opacity};\n  pointer-events: none;\n  z-index: 0;\n}`);
  }

  // ── Container INNER layout CSS (.np-inner-{id}) ──
  if (section.section_type === 'container') {
    const innerSelector = `.np-inner-${section.id}`;
    const innerBase: string[] = [];
    const innerTablet: string[] = [];
    const innerDesktop: string[] = [];
    const layout = (section.layout || {}) as ContainerLayout;

    const displayType = layout.type || 'flex';
    innerBase.push(`  display: ${displayType};`);

    if (displayType === 'flex') {
      if (layout.direction) innerBase.push(`  flex-direction: ${layout.direction};`);
      if (layout.wrap) innerBase.push(`  flex-wrap: ${layout.wrap};`);
      if (layout.justifyContent) innerBase.push(`  justify-content: ${layout.justifyContent};`);
      if (layout.alignItems) innerBase.push(`  align-items: ${layout.alignItems};`);
    }

    if (displayType === 'grid') {
      if (layout.columns) innerBase.push(`  grid-template-columns: ${layout.columns};`);
      if (layout.rows) innerBase.push(`  grid-template-rows: ${layout.rows};`);
      if (layout.justifyContent) innerBase.push(`  justify-content: ${layout.justifyContent};`);
      if (layout.alignItems) innerBase.push(`  align-items: ${layout.alignItems};`);
    }

    pushDecl(layout.gap, 'gap', innerBase, innerTablet, innerDesktop);
    pushDecl(layout.columnGap, 'column-gap', innerBase, innerTablet, innerDesktop);
    pushDecl(layout.rowGap, 'row-gap', innerBase, innerTablet, innerDesktop);

    if (innerBase.length > 0) {
      parts.push(`${innerSelector} {\n${innerBase.join('\n')}\n}`);
    }
    if (innerTablet.length > 0) {
      parts.push(`@media (min-width: ${TABLET_MIN}px) {\n  ${innerSelector} {\n  ${innerTablet.join('\n  ')}\n  }\n}`);
    }
    if (innerDesktop.length > 0) {
      parts.push(`@media (min-width: ${DESKTOP_MIN}px) {\n  ${innerSelector} {\n  ${innerDesktop.join('\n  ')}\n  }\n}`);
    }
  }

  // ── Hover state CSS — resolve responsive values ──
  const hoverDecls: string[] = [];
  const resolveHover = (key: string) => {
    const val = style[key];
    if (!val) return '';
    if (typeof val === 'string') return val;
    if (isResponsiveValue(val)) return resolveResponsive(val).mobile || '';
    return '';
  };
  const hBgColor = resolveHover('hoverBackgroundColor');
  const hBgGrad = resolveHover('hoverBackgroundGradient');
  const hBorderColor = resolveHover('hoverBorderColor');
  const hTextColor = resolveHover('hoverColor');
  if (hBgColor) hoverDecls.push(`  background-color: ${hBgColor};`);
  if (hBgGrad) hoverDecls.push(`  background: ${hBgGrad};`);
  if (hBorderColor) hoverDecls.push(`  border-color: ${hBorderColor};`);
  if (hTextColor) hoverDecls.push(`  color: ${hTextColor};`);

  if (hoverDecls.length > 0) {
    parts.push(`${selector}:hover {\n  transition: all 0.3s ease;\n${hoverDecls.join('\n')}\n}`);
  }

  // Append custom CSS
  if (section.custom_css) {
    parts.push(section.custom_css);
  }

  return parts.join('\n\n');
}

// ─── Builder-specific CSS (no @media, resolves for active breakpoint) ────

/** Resolve a responsive value for a specific breakpoint */
function resolveForBreakpoint(val: ResponsiveString | undefined, bp: Breakpoint): string {
  return getValueForBreakpoint(val, bp);
}

/**
 * Generate CSS for builder canvas preview — resolves all responsive values
 * for the active breakpoint. No @media queries, so the canvas preview
 * always shows the correct styles regardless of viewport width.
 */
export function generateBuilderCSS(section: Section, bp: Breakpoint): string {
  const selector = buildSelector(section);
  const decls: string[] = [];
  const style = (section.style || {}) as SectionStyle;

  for (const [key, val] of Object.entries(style)) {
    if (val === undefined || val === null || val === '') continue;
    if (SKIP_STYLE_KEYS.has(key)) continue;

    const cssProp = STYLE_KEY_MAP[key] || toKebab(key);

    // Special: backgroundImage needs url(), fontFamily needs quotes
    if (key === 'backgroundImage') {
      const resolved = resolveForBreakpoint(val as ResponsiveString, bp);
      if (resolved) decls.push(`  background-image: url(${resolved});`);
      continue;
    }
    if (key === 'fontFamily') {
      const resolved = resolveForBreakpoint(val as ResponsiveString, bp);
      if (resolved) {
        const quoted = resolved.includes(' ') ? `"${resolved}", sans-serif` : `${resolved}, sans-serif`;
        decls.push(`  font-family: ${quoted};`);
      }
      continue;
    }

    // All other: resolve for active breakpoint
    if (isResponsiveValue(val) || ALWAYS_RESPONSIVE_KEYS.has(key)) {
      const resolved = resolveForBreakpoint(val as ResponsiveString, bp);
      if (resolved) decls.push(`  ${cssProp}: ${resolved};`);
    } else if (typeof val === 'string') {
      decls.push(`  ${cssProp}: ${val};`);
    } else if (typeof val === 'number') {
      decls.push(`  ${cssProp}: ${val};`);
    }
  }

  // Background defaults
  if (style.backgroundImage) {
    if (!style.backgroundSize) decls.push('  background-size: cover;');
    if (!style.backgroundPosition) decls.push('  background-position: center;');
    if (!style.backgroundRepeat) decls.push('  background-repeat: no-repeat;');
  }

  if (style.zIndex) decls.push(`  z-index: ${style.zIndex};`);
  if (style.position) decls.push(`  position: ${style.position};`);

  // Flex child
  if (style.flexSize) {
    switch (style.flexSize) {
      case 'none': decls.push('  flex: 0 0 auto;'); break;
      case 'grow': decls.push('  flex: 1 0 0%;'); break;
      case 'shrink': decls.push('  flex: 0 1 auto;'); break;
      case 'custom':
        if (style.flexGrow) decls.push(`  flex-grow: ${style.flexGrow};`);
        if (style.flexShrink) decls.push(`  flex-shrink: ${style.flexShrink};`);
        if (style.flexBasis) decls.push(`  flex-basis: ${style.flexBasis};`);
        break;
    }
  }
  if (style.order) decls.push(`  order: ${style.order};`);

  // Container sizing
  if (section.section_type === 'container') {
    const layout = (section.layout || {}) as ContainerLayout;
    decls.push('  width: 100%;');
    if (layout.contentWidth === 'boxed' && layout.maxWidth) {
      const mw = resolveForBreakpoint(layout.maxWidth, bp);
      if (mw) decls.push(`  max-width: ${mw};`);
      decls.push('  margin-left: auto;');
      decls.push('  margin-right: auto;');
    }
    if (layout.width) {
      const w = resolveForBreakpoint(layout.width, bp);
      if (w) decls.push(`  width: ${w};`);
    }
  }

  // Overlay
  const builderOverlayColor = resolveForBreakpoint(style.backgroundOverlayColor as ResponsiveString, bp);
  if (builderOverlayColor) {
    decls.push('  position: relative;');
  }

  // Visibility for current breakpoint
  const vis = section.responsiveVisibility;
  if (vis && !vis[bp]) {
    decls.push('  display: none !important;');
  }

  // Safari compatibility
  decls.push('  -webkit-font-smoothing: antialiased;');
  decls.push('  -moz-osx-font-smoothing: grayscale;');

  const parts: string[] = [];
  if (decls.length > 0) {
    parts.push(`${selector} {\n${decls.join('\n')}\n}`);
  }

  // Overlay ::before
  if (builderOverlayColor) {
    const overlayOpacity = parseInt(resolveForBreakpoint(style.backgroundOverlayOpacity as ResponsiveString, bp) || '50') / 100;
    parts.push(`${selector}::before {\n  content: "";\n  position: absolute;\n  top: 0; right: 0; bottom: 0; left: 0;\n  background-color: ${builderOverlayColor};\n  opacity: ${overlayOpacity};\n  pointer-events: none;\n  z-index: 0;\n}`);
  }

  // Container inner layout
  if (section.section_type === 'container') {
    const innerSelector = `.np-inner-${section.id}`;
    const innerDecls: string[] = [];
    const layout = (section.layout || {}) as ContainerLayout;
    const displayType = layout.type || 'flex';
    innerDecls.push(`  display: ${displayType};`);

    if (displayType === 'flex') {
      if (layout.direction) innerDecls.push(`  flex-direction: ${layout.direction};`);
      if (layout.wrap) innerDecls.push(`  flex-wrap: ${layout.wrap};`);
      if (layout.justifyContent) innerDecls.push(`  justify-content: ${layout.justifyContent};`);
      if (layout.alignItems) innerDecls.push(`  align-items: ${layout.alignItems};`);
    }
    if (displayType === 'grid') {
      if (layout.columns) innerDecls.push(`  grid-template-columns: ${layout.columns};`);
      if (layout.rows) innerDecls.push(`  grid-template-rows: ${layout.rows};`);
      if (layout.justifyContent) innerDecls.push(`  justify-content: ${layout.justifyContent};`);
      if (layout.alignItems) innerDecls.push(`  align-items: ${layout.alignItems};`);
    }

    const gap = resolveForBreakpoint(layout.gap, bp);
    if (gap) innerDecls.push(`  gap: ${gap};`);
    const cg = resolveForBreakpoint(layout.columnGap, bp);
    if (cg) innerDecls.push(`  column-gap: ${cg};`);
    const rg = resolveForBreakpoint(layout.rowGap, bp);
    if (rg) innerDecls.push(`  row-gap: ${rg};`);

    if (innerDecls.length > 0) {
      parts.push(`${innerSelector} {\n${innerDecls.join('\n')}\n}`);
    }
  }

  // Hover — resolve responsive values for current breakpoint
  const hoverDecls2: string[] = [];
  const rh = (key: string) => resolveForBreakpoint(style[key] as ResponsiveString, bp);
  const hBg2 = rh('hoverBackgroundColor');
  const hGrad2 = rh('hoverBackgroundGradient');
  const hBorder2 = rh('hoverBorderColor');
  const hText2 = rh('hoverColor');
  if (hBg2) hoverDecls2.push(`  background-color: ${hBg2};`);
  if (hGrad2) hoverDecls2.push(`  background: ${hGrad2};`);
  if (hBorder2) hoverDecls2.push(`  border-color: ${hBorder2};`);
  if (hText2) hoverDecls2.push(`  color: ${hText2};`);
  if (hoverDecls2.length > 0) {
    parts.push(`${selector}:hover {\n  transition: all 0.3s ease;\n${hoverDecls2.join('\n')}\n}`);
  }

  if (section.custom_css) parts.push(section.custom_css);

  return parts.join('\n\n');
}
