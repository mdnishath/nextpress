import { useState } from '@wordpress/element';
import { useTheme } from '../../../api/useTheme';
import type { ContentField } from '../../../types/builder';

interface FieldProps {
  field: ContentField;
  value: unknown;
  onChange: (value: unknown) => void;
}

export function ColorField({ field, value, onChange }: FieldProps) {
  const strVal = typeof value === 'string' ? value : '';
  const { theme } = useTheme();
  const [showPicker, setShowPicker] = useState(false);

  const themeColors = theme?.colors ? Object.values(theme.colors) : [];

  return (
    <div className="npb-field">
      <label className="npb-field__label">{field.label}</label>

      {/* Theme color swatches */}
      {themeColors.length > 0 && (
        <div style={{ display: 'flex', gap: 4, marginBottom: 8, flexWrap: 'wrap' }}>
          {themeColors.map((color, i) => (
            <button
              key={i}
              onClick={() => onChange(color)}
              title={color as string}
              style={{
                width: 28, height: 28, borderRadius: 6,
                background: color as string,
                border: strVal === color ? '2px solid #7c3aed' : '2px solid #e5e7eb',
                cursor: 'pointer', padding: 0,
                boxShadow: strVal === color ? '0 0 0 2px #ede9fe' : 'none',
              }}
            />
          ))}
        </div>
      )}

      {/* Color input row */}
      <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
        <div
          onClick={() => setShowPicker(!showPicker)}
          style={{
            width: 36, height: 36, borderRadius: 6,
            background: strVal || '#ffffff',
            border: '2px solid #e5e7eb',
            cursor: 'pointer', flexShrink: 0,
          }}
        />
        <input
          className="npb-field__input"
          type="text"
          value={strVal}
          placeholder="#000000"
          onChange={(e) => onChange(e.target.value)}
          style={{ flex: 1, fontFamily: 'var(--npb-font-mono)', fontSize: 13 }}
        />
      </div>

      {/* Native color picker */}
      {showPicker && (
        <div style={{ marginTop: 8 }}>
          <input
            type="color"
            value={strVal || '#000000'}
            onChange={(e) => onChange(e.target.value)}
            style={{ width: '100%', height: 40, padding: 2, cursor: 'pointer', border: '1px solid #e5e7eb', borderRadius: 6 }}
          />
        </div>
      )}
    </div>
  );
}
