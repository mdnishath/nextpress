/**
 * Responsive value utilities — mobile-first design.
 * Mobile is the base. Tablet falls back to mobile. Desktop falls back to tablet then mobile.
 */

import type { Breakpoint, ResponsiveValue, ResponsiveString } from '../types/builder';
import { isResponsiveValue } from '../types/builder';

/**
 * Resolve a value for a specific breakpoint (mobile-first fallback chain).
 * - mobile: returns mobile
 * - tablet: returns tablet ?? mobile
 * - desktop: returns desktop ?? tablet ?? mobile
 */
export function getValueForBreakpoint(val: ResponsiveString | undefined, breakpoint: Breakpoint): string {
  if (val === undefined || val === null) return '';
  if (typeof val === 'string') return val;
  if (!isResponsiveValue(val)) return '';

  switch (breakpoint) {
    case 'mobile':
      return val.mobile || '';
    case 'tablet':
      return val.tablet ?? val.mobile ?? '';
    case 'desktop':
      return val.desktop ?? val.tablet ?? val.mobile ?? '';
    default:
      return val.mobile || '';
  }
}

/**
 * Update a single breakpoint's value. Returns a plain string if all breakpoints
 * are the same, or a ResponsiveValue object otherwise.
 */
export function setValueForBreakpoint(
  current: ResponsiveString | undefined,
  breakpoint: Breakpoint,
  newVal: string,
): ResponsiveString {
  // Build the full responsive object from current value
  let mobile = '';
  let tablet: string | undefined;
  let desktop: string | undefined;

  if (typeof current === 'string') {
    mobile = current;
  } else if (isResponsiveValue(current)) {
    mobile = current.mobile;
    tablet = current.tablet;
    desktop = current.desktop;
  }

  // Apply the new value
  switch (breakpoint) {
    case 'mobile':
      mobile = newVal;
      break;
    case 'tablet':
      tablet = newVal || undefined;
      break;
    case 'desktop':
      desktop = newVal || undefined;
      break;
  }

  // Simplify: if all breakpoints are same or missing, return plain string
  const effectiveTablet = tablet ?? mobile;
  const effectiveDesktop = desktop ?? effectiveTablet;
  if (effectiveTablet === mobile && effectiveDesktop === mobile) return mobile;
  if (!tablet && !desktop) return mobile;

  return { mobile, tablet, desktop };
}

/**
 * Check if a responsive value has any breakpoint-specific overrides.
 */
export function hasBreakpointOverrides(val: ResponsiveString | undefined): { tablet: boolean; desktop: boolean } {
  if (!val || typeof val === 'string' || !isResponsiveValue(val)) {
    return { tablet: false, desktop: false };
  }
  return {
    tablet: val.tablet !== undefined && val.tablet !== val.mobile,
    desktop: val.desktop !== undefined && val.desktop !== (val.tablet ?? val.mobile),
  };
}
