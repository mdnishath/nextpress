import type { ContentField } from '../../../types/builder';

interface FieldProps {
  field: ContentField;
  value: unknown;
  onChange: (value: unknown) => void;
}

export function SelectField({ field, value, onChange }: FieldProps) {
  const strVal = typeof value === 'string' ? value : '';
  const options = Array.isArray(field.options) ? field.options : [];

  return (
    <div className="npb-field">
      <label className="npb-field__label">{field.label}</label>
      <select
        className="npb-field__input"
        value={strVal}
        onChange={(e) => onChange(e.target.value)}
      >
        <option value="">Select...</option>
        {options.map((opt, idx) => (
          <option key={opt.value ?? idx} value={opt.value ?? ''}>{opt.label ?? opt.value}</option>
        ))}
      </select>
    </div>
  );
}
