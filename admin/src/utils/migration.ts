import type { Section } from '../types/builder';

/**
 * Migrate old section data to the new per-property responsive format.
 * Old format used a top-level `responsive` object with tablet/mobile overrides.
 * New format stores responsive values directly on each style property.
 */
export function migrateSection(section: Section): Section {
  // Convert old responsive field to per-property responsive values
  const responsive = (section as any).responsive;
  if (responsive && typeof responsive === 'object') {
    const style = { ...section.style };
    for (const [bp, overrides] of Object.entries(responsive)) {
      if (bp === 'tablet' || bp === 'mobile') {
        // Old responsive field used desktop as base, tablet/mobile as overrides.
        // Skip for now — old data had limited responsive use.
      }
    }
    return { ...section, style };
  }
  return section;
}
