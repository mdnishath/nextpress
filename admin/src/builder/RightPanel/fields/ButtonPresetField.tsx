import { useButtonPresets } from '../../../api/useTheme';
import type { ContentField } from '../../../types/builder';

interface FieldProps {
  field: ContentField;
  value: unknown;
  onChange: (value: unknown) => void;
}

export function ButtonPresetField({ field, value, onChange }: FieldProps) {
  const strVal = typeof value === 'string' ? value : '';
  const { presets, loading } = useButtonPresets();

  if (loading) {
    return (
      <div className="npb-field">
        <label className="npb-field__label">{field.label}</label>
        <div style={{ color: '#a1a1aa', fontSize: 12 }}>Loading presets...</div>
      </div>
    );
  }

  return (
    <div className="npb-field">
      <label className="npb-field__label">{field.label}</label>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
        {presets.map((preset) => (
          <button
            key={preset.slug}
            onClick={() => onChange(preset.slug)}
            style={{
              padding: '10px 16px',
              border: `2px solid ${strVal === preset.slug ? '#7c3aed' : '#e5e7eb'}`,
              borderRadius: 8,
              background: strVal === preset.slug ? '#ede9fe' : '#fff',
              cursor: 'pointer',
              textAlign: 'left',
              fontSize: 13,
              fontWeight: 500,
              color: '#374151',
              transition: 'all 0.15s',
            }}
          >
            {preset.name}
          </button>
        ))}
        {presets.length === 0 && (
          <div style={{ color: '#a1a1aa', fontSize: 12 }}>No button presets available</div>
        )}
      </div>
    </div>
  );
}
