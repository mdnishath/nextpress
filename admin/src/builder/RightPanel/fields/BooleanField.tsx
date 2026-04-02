import type { ContentField } from '../../../types/builder';

interface FieldProps {
  field: ContentField;
  value: unknown;
  onChange: (value: unknown) => void;
}

export function BooleanField({ field, value, onChange }: FieldProps) {
  const checked = value === true || value === 1 || value === '1' || value === 'true';

  return (
    <div className="npb-field">
      <label style={{
        display: 'flex', alignItems: 'center', gap: 10, cursor: 'pointer',
        padding: '8px 0',
      }}>
        <div
          onClick={() => onChange(!checked)}
          style={{
            width: 40, height: 22, borderRadius: 11,
            background: checked ? '#7c3aed' : '#d1d5db',
            position: 'relative', transition: 'background 0.2s', cursor: 'pointer',
            flexShrink: 0,
          }}
        >
          <div style={{
            width: 18, height: 18, borderRadius: '50%',
            background: '#fff', position: 'absolute', top: 2,
            left: checked ? 20 : 2,
            transition: 'left 0.2s',
            boxShadow: '0 1px 3px rgba(0,0,0,0.2)',
          }} />
        </div>
        <span style={{ fontSize: 13, fontWeight: 500, color: '#374151' }}>{field.label}</span>
      </label>
    </div>
  );
}
