import { useState, useEffect } from '@wordpress/element';
import { apiGet } from '../../../api/useApi';
import type { ContentField } from '../../../types/builder';

interface FieldProps {
  field: ContentField;
  value: unknown;
  onChange: (value: unknown) => void;
}

interface FormItem {
  id: number;
  name: string;
  slug: string;
}

export function FormSelectField({ field, value, onChange }: FieldProps) {
  const strVal = typeof value === 'string' ? value : String(value ?? '');
  const [forms, setForms] = useState<FormItem[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    apiGet<unknown>('/forms')
      .then((res) => {
        if (res && typeof res === 'object' && 'data' in res) {
          const d = (res as Record<string, unknown>).data;
          if (Array.isArray(d)) setForms(d);
        } else if (Array.isArray(res)) {
          setForms(res);
        }
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  return (
    <div className="npb-field">
      <label className="npb-field__label">{field.label}</label>
      <select
        className="npb-field__input"
        value={strVal}
        onChange={(e) => onChange(e.target.value)}
        disabled={loading}
      >
        <option value="">{loading ? 'Loading forms...' : 'Select a form...'}</option>
        {forms.map((form) => (
          <option key={form.id} value={String(form.id)}>{form.name}</option>
        ))}
      </select>
      {!loading && forms.length === 0 && (
        <div style={{ marginTop: 4, fontSize: 11, color: '#a1a1aa' }}>
          No forms created yet. Go to Forms to create one.
        </div>
      )}
    </div>
  );
}
