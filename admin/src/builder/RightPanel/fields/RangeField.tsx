import type { ContentField } from '../../../types/builder';

interface FieldProps {
  field: ContentField;
  value: unknown;
  onChange: (value: unknown) => void;
}

export function RangeField({ field, value, onChange }: FieldProps) {
  const numVal = typeof value === 'number' ? value : Number(value) || field.min || 0;

  return (
    <div className="npb-field">
      <label className="npb-field__label">
        {field.label}
        <span style={{ float: 'right', fontWeight: 400, color: '#09090b' }}>{numVal}</span>
      </label>
      <input
        type="range"
        value={numVal}
        min={field.min ?? 0}
        max={field.max ?? 100}
        step={field.step ?? 1}
        onChange={(e) => onChange(Number(e.target.value))}
        style={{ width: '100%', accentColor: '#7c3aed' }}
      />
    </div>
  );
}
