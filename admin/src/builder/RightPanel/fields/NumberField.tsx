import type { ContentField } from '../../../types/builder';

interface FieldProps {
  field: ContentField;
  value: unknown;
  onChange: (value: unknown) => void;
}

export function NumberField({ field, value, onChange }: FieldProps) {
  const numVal = typeof value === 'number' ? value : (typeof value === 'string' ? Number(value) || '' : '');

  return (
    <div className="npb-field">
      <label className="npb-field__label">{field.label}</label>
      <input
        className="npb-field__input"
        type="number"
        value={numVal}
        min={field.min}
        max={field.max}
        step={field.step ?? 1}
        placeholder={field.placeholder}
        onChange={(e) => onChange(e.target.value === '' ? '' : Number(e.target.value))}
      />
    </div>
  );
}
