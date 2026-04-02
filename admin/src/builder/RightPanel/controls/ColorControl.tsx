import { useState } from '@wordpress/element';
import { useTheme } from '../../../api/useTheme';

interface ColorControlProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
}

export function ColorControl({ label, value, onChange }: ColorControlProps) {
  const { theme } = useTheme();
  const [showPicker, setShowPicker] = useState(false);
  const themeColors = theme?.colors ? Object.values(theme.colors) : [];

  return (
    <div className="npb-field">
      <label className="npb-field__label">{label}</label>

      {themeColors.length > 0 && (
        <div style={{ display: 'flex', gap: 4, marginBottom: 8, flexWrap: 'wrap' }}>
          {themeColors.map((color, i) => (
            <button
              key={i}
              onClick={() => onChange(color as string)}
              style={{
                width: 24, height: 24, borderRadius: 4,
                background: color as string,
                border: value === color ? '2px solid #7c3aed' : '1px solid #e5e7eb',
                cursor: 'pointer', padding: 0,
              }}
            />
          ))}
        </div>
      )}

      <div style={{ display: 'flex', gap: 6, alignItems: 'center' }}>
        <div
          onClick={() => setShowPicker(!showPicker)}
          style={{
            width: 32, height: 32, borderRadius: 6,
            background: value || 'linear-gradient(45deg, #ccc 25%, transparent 25%, transparent 75%, #ccc 75%), linear-gradient(45deg, #ccc 25%, transparent 25%, transparent 75%, #ccc 75%)',
            backgroundSize: '8px 8px',
            backgroundPosition: '0 0, 4px 4px',
            border: '1px solid #e5e7eb', cursor: 'pointer', flexShrink: 0,
            position: 'relative',
          }}
        >
          {value && (
            <div style={{
              position: 'absolute', top: 0, right: 0, bottom: 0, left: 0, borderRadius: 5,
              background: value,
            }} />
          )}
        </div>
        <input
          className="npb-field__input"
          type="text"
          value={value}
          placeholder="transparent"
          onChange={(e) => onChange(e.target.value)}
          style={{ flex: 1, fontFamily: 'var(--npb-font-mono)', fontSize: 12 }}
        />
        {value && (
          <button
            onClick={() => onChange('')}
            style={{ fontSize: 11, color: '#ef4444', background: 'none', border: 'none', cursor: 'pointer' }}
          >
            Clear
          </button>
        )}
      </div>

      {showPicker && (
        <input
          type="color"
          value={value || '#000000'}
          onChange={(e) => onChange(e.target.value)}
          style={{
            width: '100%', height: 36, padding: 2, cursor: 'pointer',
            border: '1px solid #e5e7eb', borderRadius: 6, marginTop: 6,
          }}
        />
      )}
    </div>
  );
}
