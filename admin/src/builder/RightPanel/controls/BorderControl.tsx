import { useState } from '@wordpress/element';
import { Link2, Unlink2 } from 'lucide-react';
import { useBuilderStore } from '../../../store/builderStore';
import { ResponsiveControl } from './ResponsiveControl';
import { getValueForBreakpoint } from '../../../utils/responsive';
import type { SectionStyle } from '../../../types/builder';

interface BorderControlProps {
  sectionId: string;
  style: SectionStyle;
}

const BORDER_STYLES = ['none', 'solid', 'dashed', 'dotted', 'double'];

export function BorderControl({ sectionId, style }: BorderControlProps) {
  const [linkedRadius, setLinkedRadius] = useState(true);
  const { breakpoint, updateResponsiveStyle, updateStyle } = useBuilderStore();

  return (
    <div className="npb-field">
      <label className="npb-field__label">Border</label>

      {/* Width (responsive) + Style + Color */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 6, marginBottom: 10 }}>
        <ResponsiveControl label="Width" value={style.borderWidth}>
          <input
            className="npb-field__input"
            type="text"
            value={getValueForBreakpoint(style.borderWidth, breakpoint)}
            onChange={(e) => updateResponsiveStyle(sectionId, 'borderWidth', breakpoint, e.target.value)}
            placeholder="0"
            style={{ textAlign: 'center' }}
          />
        </ResponsiveControl>

        <div>
          <label style={{ fontSize: 10, color: '#a1a1aa', marginBottom: 2, display: 'block' }}>Style</label>
          <select
            className="npb-field__input"
            value={style.borderStyle || 'none'}
            onChange={(e) => updateStyle(sectionId, { borderStyle: e.target.value })}
          >
            {BORDER_STYLES.map((s) => (
              <option key={s} value={s}>{s}</option>
            ))}
          </select>
        </div>
      </div>

      {/* Border Color */}
      <div style={{ marginBottom: 10 }}>
        <label style={{ fontSize: 10, color: '#a1a1aa', marginBottom: 2, display: 'block' }}>Color</label>
        <input
          type="color"
          value={style.borderColor || '#000000'}
          onChange={(e) => updateStyle(sectionId, { borderColor: e.target.value })}
          style={{ width: '100%', height: 34, padding: 2, cursor: 'pointer', borderRadius: 4, border: '1px solid #e5e7eb' }}
        />
      </div>

      {/* Border Radius (responsive) */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6 }}>
        <label style={{ fontSize: 11, color: '#6b7280', fontWeight: 600 }}>Radius</label>
        <button
          onClick={() => setLinkedRadius(!linkedRadius)}
          style={{ background: 'none', border: 'none', cursor: 'pointer', color: linkedRadius ? '#7c3aed' : '#a1a1aa', padding: 2 }}
        >
          {linkedRadius ? <Link2 size={12} /> : <Unlink2 size={12} />}
        </button>
      </div>

      {linkedRadius ? (
        <ResponsiveControl label="Radius" value={style.borderRadius}>
          <input
            className="npb-field__input"
            type="text"
            value={getValueForBreakpoint(style.borderRadius, breakpoint)}
            onChange={(e) => updateResponsiveStyle(sectionId, 'borderRadius', breakpoint, e.target.value)}
            placeholder="8px"
          />
        </ResponsiveControl>
      ) : (
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 6 }}>
          {['TopLeft', 'TopRight', 'BottomRight', 'BottomLeft'].map((corner) => (
            <input
              key={corner}
              className="npb-field__input"
              type="text"
              value={(style[`borderRadius${corner}` as keyof SectionStyle] as string) || ''}
              onChange={(e) => updateStyle(sectionId, { [`borderRadius${corner}`]: e.target.value })}
              placeholder={corner.replace(/([A-Z])/g, ' $1').trim().toLowerCase()}
              style={{ fontSize: 12 }}
            />
          ))}
        </div>
      )}
    </div>
  );
}
