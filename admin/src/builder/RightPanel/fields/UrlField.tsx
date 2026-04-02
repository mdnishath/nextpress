import { ExternalLink } from 'lucide-react';
import type { ContentField } from '../../../types/builder';

interface FieldProps {
  field: ContentField;
  value: unknown;
  onChange: (value: unknown) => void;
}

export function UrlField({ field, value, onChange }: FieldProps) {
  const strVal = typeof value === 'string' ? value : '';

  return (
    <div className="npb-field">
      <label className="npb-field__label">{field.label}</label>
      <div style={{ display: 'flex', gap: 6, alignItems: 'center' }}>
        <input
          className="npb-field__input"
          type="url"
          value={strVal}
          placeholder={field.placeholder || 'https://...'}
          onChange={(e) => onChange(e.target.value)}
          style={{ flex: 1 }}
        />
        {strVal && (
          <a
            href={strVal}
            target="_blank"
            rel="noopener noreferrer"
            title="Open in new tab"
            style={{
              width: 36, height: 36, borderRadius: 6,
              border: '1px solid #e5e7eb', background: '#fafafa',
              display: 'flex', alignItems: 'center', justifyContent: 'center',
              color: '#6b7280', textDecoration: 'none', flexShrink: 0,
            }}
          >
            <ExternalLink size={16} />
          </a>
        )}
      </div>
    </div>
  );
}
