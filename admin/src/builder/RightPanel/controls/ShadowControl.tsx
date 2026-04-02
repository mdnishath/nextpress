import type { SectionStyle } from '../../../types/builder';

interface ShadowControlProps {
  style: SectionStyle;
  onChange: (updates: Partial<SectionStyle>) => void;
}

const SHADOW_PRESETS = [
  { label: 'None', value: 'none' },
  { label: 'SM', value: '0 1px 2px rgba(0,0,0,0.05)' },
  { label: 'MD', value: '0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1)' },
  { label: 'LG', value: '0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1)' },
  { label: 'XL', value: '0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1)' },
  { label: '2XL', value: '0 25px 50px -12px rgba(0,0,0,0.25)' },
];

export function ShadowControl({ style, onChange }: ShadowControlProps) {
  const current = style.boxShadow || '';

  return (
    <div className="npb-field">
      <label className="npb-field__label">Box Shadow</label>

      {/* Presets */}
      <div style={{ display: 'flex', gap: 4, marginBottom: 8, flexWrap: 'wrap' }}>
        {SHADOW_PRESETS.map((preset) => (
          <button
            key={preset.label}
            onClick={() => onChange({ boxShadow: preset.value })}
            style={{
              padding: '4px 10px', border: '1px solid #e5e7eb',
              borderRadius: 4, fontSize: 11, fontWeight: 600, cursor: 'pointer',
              background: current === preset.value ? '#ede9fe' : '#fff',
              color: current === preset.value ? '#7c3aed' : '#6b7280',
              borderColor: current === preset.value ? '#7c3aed' : '#e5e7eb',
            }}
          >
            {preset.label}
          </button>
        ))}
      </div>

      {/* Custom input */}
      <input
        className="npb-field__input"
        type="text"
        value={current}
        onChange={(e) => onChange({ boxShadow: e.target.value })}
        placeholder="0 4px 6px rgba(0,0,0,0.1)"
        style={{ fontSize: 12, fontFamily: 'var(--npb-font-mono)' }}
      />

      {/* Preview */}
      {current && current !== 'none' && (
        <div style={{
          width: '100%', height: 40, marginTop: 8,
          background: '#fff', borderRadius: 8,
          boxShadow: current,
        }} />
      )}
    </div>
  );
}
