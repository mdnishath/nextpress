import { generateSectionCSS, generateBuilderCSS } from '../../utils/cssGenerator';
import { useBuilderStore } from '../../store/builderStore';
import type { Section } from '../../types/builder';

export function SectionStyleTag({ section }: { section: Section }) {
  const breakpoint = useBuilderStore((s) => s.breakpoint);
  // In builder: resolve all responsive values for active breakpoint (no @media)
  // This ensures canvas preview matches the selected device
  const css = generateBuilderCSS(section, breakpoint);
  return <style>{css}</style>;
}
