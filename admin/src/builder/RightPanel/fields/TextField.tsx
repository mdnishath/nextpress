import type { ContentField } from '../../../types/builder';

interface FieldProps {
  field: ContentField;
  value: unknown;
  onChange: (value: unknown) => void;
}

export function TextField({ field, value, onChange }: FieldProps) {
  return (
    <div className="npb-field">
      <label className="npb-field__label">{field.label}</label>
      <input
        className="npb-field__input"
        type="text"
        value={typeof value === 'string' ? value : ''}
        placeholder={field.placeholder}
        onChange={(e) => onChange(e.target.value)}
      />
    </div>
  );
}
