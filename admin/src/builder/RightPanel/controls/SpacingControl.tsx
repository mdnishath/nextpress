import { useState } from '@wordpress/element';
import { Link2, Unlink2 } from 'lucide-react';
import { useBuilderStore } from '../../../store/builderStore';
import { ResponsiveControl } from './ResponsiveControl';
import { getValueForBreakpoint } from '../../../utils/responsive';
import type { SectionStyle } from '../../../types/builder';

interface SpacingControlProps {
  label: 'Padding' | 'Margin';
  sectionId: string;
  style: SectionStyle;
}

type Side = 'Top' | 'Right' | 'Bottom' | 'Left';
const SIDES: Side[] = ['Top', 'Right', 'Bottom', 'Left'];

export function SpacingControl({ label, sectionId, style }: SpacingControlProps) {
  const prefix = label.toLowerCase() as 'padding' | 'margin';
  const [linked, setLinked] = useState(false);
  const { breakpoint, updateResponsiveStyle } = useBuilderStore();

  const values: Record<Side, string> = {
    Top: getValueForBreakpoint(style[`${prefix}Top`], breakpoint),
    Right: getValueForBreakpoint(style[`${prefix}Right`], breakpoint),
    Bottom: getValueForBreakpoint(style[`${prefix}Bottom`], breakpoint),
    Left: getValueForBreakpoint(style[`${prefix}Left`], breakpoint),
  };

  const handleChange = (side: Side, val: string) => {
    if (linked) {
      SIDES.forEach((s) => {
        updateResponsiveStyle(sectionId, `${prefix}${s}`, breakpoint, val);
      });
    } else {
      updateResponsiveStyle(sectionId, `${prefix}${side}`, breakpoint, val);
    }
  };

  // Show the first side's responsive value for the device dots
  const firstKey = `${prefix}Top`;

  return (
    <ResponsiveControl label={label} value={style[firstKey]}>
      <div style={{ display: 'flex', justifyContent: 'flex-end', marginBottom: 6 }}>
        <button
          onClick={() => setLinked(!linked)}
          title={linked ? 'Unlink sides' : 'Link all sides'}
          style={{
            background: 'none', border: 'none', cursor: 'pointer',
            color: linked ? '#7c3aed' : '#a1a1aa', padding: 2,
          }}
        >
          {linked ? <Link2 size={14} /> : <Unlink2 size={14} />}
        </button>
      </div>

      {/* Visual box model */}
      <div style={{
        position: 'relative',
        border: '2px solid #e5e7eb',
        borderRadius: 8,
        padding: 12,
        background: label === 'Margin' ? '#fef3c7' : '#ede9fe',
      }}>
        {/* Top */}
        <div style={{ display: 'flex', justifyContent: 'center', marginBottom: 4 }}>
          <input
            type="text"
            value={values.Top}
            onChange={(e) => handleChange('Top', e.target.value)}
            placeholder="0"
            style={{
              width: 52, textAlign: 'center', padding: '3px 4px',
              border: '1px solid #d1d5db', borderRadius: 4, fontSize: 12,
              background: '#fff', outline: 'none',
            }}
          />
        </div>

        {/* Left + Center + Right */}
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <input
            type="text"
            value={values.Left}
            onChange={(e) => handleChange('Left', e.target.value)}
            placeholder="0"
            style={{
              width: 52, textAlign: 'center', padding: '3px 4px',
              border: '1px solid #d1d5db', borderRadius: 4, fontSize: 12,
              background: '#fff', outline: 'none',
            }}
          />
          <div style={{
            flex: 1, margin: '0 8px', height: 32,
            border: '1px dashed #d1d5db', borderRadius: 4,
            background: label === 'Margin' ? '#ede9fe' : '#fff',
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            fontSize: 10, color: '#a1a1aa', fontWeight: 600, textTransform: 'uppercase',
          }}>
            {label === 'Margin' ? 'padding' : 'content'}
          </div>
          <input
            type="text"
            value={values.Right}
            onChange={(e) => handleChange('Right', e.target.value)}
            placeholder="0"
            style={{
              width: 52, textAlign: 'center', padding: '3px 4px',
              border: '1px solid #d1d5db', borderRadius: 4, fontSize: 12,
              background: '#fff', outline: 'none',
            }}
          />
        </div>

        {/* Bottom */}
        <div style={{ display: 'flex', justifyContent: 'center', marginTop: 4 }}>
          <input
            type="text"
            value={values.Bottom}
            onChange={(e) => handleChange('Bottom', e.target.value)}
            placeholder="0"
            style={{
              width: 52, textAlign: 'center', padding: '3px 4px',
              border: '1px solid #d1d5db', borderRadius: 4, fontSize: 12,
              background: '#fff', outline: 'none',
            }}
          />
        </div>
      </div>
    </ResponsiveControl>
  );
}
