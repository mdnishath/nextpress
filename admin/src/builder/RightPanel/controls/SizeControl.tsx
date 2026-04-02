import { useBuilderStore } from '../../../store/builderStore';
import { ResponsiveControl } from './ResponsiveControl';
import { getValueForBreakpoint } from '../../../utils/responsive';
import type { SectionStyle } from '../../../types/builder';

interface SizeControlProps {
  sectionId: string;
  style: SectionStyle;
}

export function SizeControl({ sectionId, style }: SizeControlProps) {
  const { breakpoint, updateResponsiveStyle, updateStyle } = useBuilderStore();

  return (
    <div>
      <ResponsiveControl label="Width" value={style.width}>
        <input
          className="npb-field__input"
          type="text"
          value={getValueForBreakpoint(style.width, breakpoint)}
          onChange={(e) => updateResponsiveStyle(sectionId, 'width', breakpoint, e.target.value)}
          placeholder="auto"
        />
      </ResponsiveControl>

      <ResponsiveControl label="Max Width" value={style.maxWidth}>
        <input
          className="npb-field__input"
          type="text"
          value={getValueForBreakpoint(style.maxWidth, breakpoint)}
          onChange={(e) => updateResponsiveStyle(sectionId, 'maxWidth', breakpoint, e.target.value)}
          placeholder="none"
        />
      </ResponsiveControl>

      <ResponsiveControl label="Min Height" value={style.minHeight}>
        <input
          className="npb-field__input"
          type="text"
          value={getValueForBreakpoint(style.minHeight, breakpoint)}
          onChange={(e) => updateResponsiveStyle(sectionId, 'minHeight', breakpoint, e.target.value)}
          placeholder="auto"
        />
      </ResponsiveControl>

      <div className="npb-field">
        <label className="npb-field__label">Overflow</label>
        <select
          className="npb-field__input"
          value={(style.overflow as string) || ''}
          onChange={(e) => updateStyle(sectionId, { overflow: e.target.value })}
        >
          <option value="">Default</option>
          <option value="visible">Visible</option>
          <option value="hidden">Hidden</option>
          <option value="scroll">Scroll</option>
          <option value="auto">Auto</option>
        </select>
      </div>
    </div>
  );
}
