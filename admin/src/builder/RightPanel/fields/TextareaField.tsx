import type { ContentField } from '../../../types/builder';

interface FieldProps {
  field: ContentField;
  value: unknown;
  onChange: (value: unknown) => void;
}

export function TextareaField({ field, value, onChange }: FieldProps) {
  return (
    <div className="npb-field">
      <label className="npb-field__label">{field.label}</label>
      <textarea
        className="npb-field__input"
        rows={4}
        value={typeof value === 'string' ? value : ''}
        placeholder={field.placeholder}
        onChange={(e) => onChange(e.target.value)}
        style={{ resize: 'vertical' }}
      />
    </div>
  );
}
