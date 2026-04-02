import { useBuilderStore } from '../../../store/builderStore';
import { ResponsiveControl } from './ResponsiveControl';
import { getValueForBreakpoint } from '../../../utils/responsive';
import type { SectionStyle } from '../../../types/builder';

interface TypographyControlProps {
  sectionId: string;
  style: SectionStyle;
}

const FONT_FAMILIES = [
  'Inter', 'Roboto', 'Open Sans', 'Lato', 'Poppins', 'Montserrat',
  'Raleway', 'Nunito', 'Playfair Display', 'Merriweather',
  'Source Sans Pro', 'PT Sans', 'Oswald', 'DM Sans', 'Space Grotesk',
];

const FONT_WEIGHTS = [
  { value: '300', label: 'Light' },
  { value: '400', label: 'Regular' },
  { value: '500', label: 'Medium' },
  { value: '600', label: 'Semi Bold' },
  { value: '700', label: 'Bold' },
  { value: '800', label: 'Extra Bold' },
];

const TEXT_TRANSFORMS = [
  { value: 'none', label: 'None' },
  { value: 'uppercase', label: 'ABC' },
  { value: 'lowercase', label: 'abc' },
  { value: 'capitalize', label: 'Abc' },
];

const TEXT_ALIGNS = [
  { value: 'left', label: 'L' },
  { value: 'center', label: 'C' },
  { value: 'right', label: 'R' },
  { value: 'justify', label: 'J' },
];

export function TypographyControl({ sectionId, style }: TypographyControlProps) {
  const { breakpoint, updateResponsiveStyle, updateStyle } = useBuilderStore();

  return (
    <div className="npb-field">
      <label className="npb-field__label">Typography</label>

      {/* Font Family */}
      <div style={{ marginBottom: 10 }}>
        <label style={{ fontSize: 11, color: '#6b7280', marginBottom: 3, display: 'block' }}>Font Family</label>
        <select
          className="npb-field__input"
          value={style.fontFamily || ''}
          onChange={(e) => updateStyle(sectionId, { fontFamily: e.target.value })}
        >
          <option value="">Default (inherit)</option>
          {FONT_FAMILIES.map((f) => (
            <option key={f} value={f} style={{ fontFamily: f }}>{f}</option>
          ))}
        </select>
      </div>

      {/* Size (responsive) + Weight row */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8, marginBottom: 10 }}>
        <ResponsiveControl label="Size" value={style.fontSize}>
          <input
            className="npb-field__input"
            type="text"
            value={getValueForBreakpoint(style.fontSize, breakpoint)}
            onChange={(e) => updateResponsiveStyle(sectionId, 'fontSize', breakpoint, e.target.value)}
            placeholder="16px"
          />
        </ResponsiveControl>
        <div>
          <label style={{ fontSize: 11, color: '#6b7280', marginBottom: 3, display: 'block' }}>Weight</label>
          <select
            className="npb-field__input"
            value={style.fontWeight || ''}
            onChange={(e) => updateStyle(sectionId, { fontWeight: e.target.value })}
          >
            <option value="">Default</option>
            {FONT_WEIGHTS.map((w) => (
              <option key={w.value} value={w.value}>{w.label} ({w.value})</option>
            ))}
          </select>
        </div>
      </div>

      {/* Line Height (responsive) + Letter Spacing (responsive) */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8, marginBottom: 10 }}>
        <ResponsiveControl label="Line Height" value={style.lineHeight}>
          <input
            className="npb-field__input"
            type="text"
            value={getValueForBreakpoint(style.lineHeight, breakpoint)}
            onChange={(e) => updateResponsiveStyle(sectionId, 'lineHeight', breakpoint, e.target.value)}
            placeholder="1.5"
          />
        </ResponsiveControl>
        <ResponsiveControl label="Letter Spacing" value={style.letterSpacing}>
          <input
            className="npb-field__input"
            type="text"
            value={getValueForBreakpoint(style.letterSpacing, breakpoint)}
            onChange={(e) => updateResponsiveStyle(sectionId, 'letterSpacing', breakpoint, e.target.value)}
            placeholder="0em"
          />
        </ResponsiveControl>
      </div>

      {/* Text Transform */}
      <div style={{ marginBottom: 10 }}>
        <label style={{ fontSize: 11, color: '#6b7280', marginBottom: 3, display: 'block' }}>Transform</label>
        <div style={{ display: 'flex', gap: 4 }}>
          {TEXT_TRANSFORMS.map((t) => (
            <button
              key={t.value}
              onClick={() => updateStyle(sectionId, { textTransform: t.value })}
              style={{
                flex: 1, padding: '6px 0', border: '1px solid #e5e7eb',
                borderRadius: 4, fontSize: 12, fontWeight: 600, cursor: 'pointer',
                background: style.textTransform === t.value ? '#ede9fe' : '#fff',
                color: style.textTransform === t.value ? '#7c3aed' : '#6b7280',
                borderColor: style.textTransform === t.value ? '#7c3aed' : '#e5e7eb',
              }}
            >
              {t.label}
            </button>
          ))}
        </div>
      </div>

      {/* Text Align */}
      <div>
        <label style={{ fontSize: 11, color: '#6b7280', marginBottom: 3, display: 'block' }}>Alignment</label>
        <div style={{ display: 'flex', gap: 4 }}>
          {TEXT_ALIGNS.map((a) => (
            <button
              key={a.value}
              onClick={() => updateStyle(sectionId, { textAlign: a.value })}
              style={{
                flex: 1, padding: '6px 0', border: '1px solid #e5e7eb',
                borderRadius: 4, fontSize: 13, fontWeight: 700, cursor: 'pointer',
                background: style.textAlign === a.value ? '#ede9fe' : '#fff',
                color: style.textAlign === a.value ? '#7c3aed' : '#6b7280',
                borderColor: style.textAlign === a.value ? '#7c3aed' : '#e5e7eb',
              }}
            >
              {a.label}
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}
