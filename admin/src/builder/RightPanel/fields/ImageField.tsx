import { useCallback } from '@wordpress/element';
import { Image, X, Upload } from 'lucide-react';
import type { ContentField } from '../../../types/builder';

interface FieldProps {
  field: ContentField;
  value: unknown;
  onChange: (value: unknown) => void;
}

declare global {
  interface Window {
    wp?: {
      media?: (opts: Record<string, unknown>) => {
        on: (event: string, cb: () => void) => unknown;
        state: () => { get: (key: string) => { first: () => { toJSON: () => { url: string; id: number; alt: string } } } };
        open: () => void;
      };
    };
  }
}

/**
 * Image field with WordPress Media Library integration.
 * Falls back to URL input if Media Library isn't available.
 */
export function ImageField({ field, value, onChange }: FieldProps) {
  const strVal = typeof value === 'string' ? value : '';

  const openMediaLibrary = useCallback(() => {
    if (!window.wp?.media) {
      // Fallback: prompt for URL
      const url = prompt('Enter image URL:', strVal);
      if (url !== null) onChange(url);
      return;
    }

    const frame = window.wp.media({
      title: `Select ${field.label}`,
      multiple: false,
      library: { type: 'image' },
      button: { text: 'Use Image' },
    });

    frame.on('select', () => {
      const attachment = frame.state().get('selection').first().toJSON();
      onChange(attachment.url);
    });

    frame.open();
  }, [field.label, strVal, onChange]);

  return (
    <div className="npb-field">
      <label className="npb-field__label">{field.label}</label>

      {strVal ? (
        <div style={{ position: 'relative', marginBottom: 8 }}>
          <img
            src={strVal}
            alt={field.label}
            style={{
              width: '100%', height: 140, objectFit: 'cover',
              borderRadius: 8, border: '1px solid #e5e7eb',
            }}
          />
          <button
            onClick={() => onChange('')}
            style={{
              position: 'absolute', top: 6, right: 6,
              width: 24, height: 24, borderRadius: '50%',
              background: 'rgba(0,0,0,0.6)', border: 'none',
              color: '#fff', cursor: 'pointer',
              display: 'flex', alignItems: 'center', justifyContent: 'center',
            }}
          >
            <X size={14} />
          </button>
          <button
            onClick={openMediaLibrary}
            style={{
              position: 'absolute', bottom: 6, right: 6,
              padding: '4px 10px', borderRadius: 6,
              background: 'rgba(0,0,0,0.6)', border: 'none',
              color: '#fff', cursor: 'pointer', fontSize: 11, fontWeight: 600,
            }}
          >
            Replace
          </button>
        </div>
      ) : (
        <button
          onClick={openMediaLibrary}
          style={{
            width: '100%', padding: 24,
            border: '2px dashed #d1d5db', borderRadius: 8,
            background: '#fafafa', cursor: 'pointer',
            display: 'flex', flexDirection: 'column',
            alignItems: 'center', gap: 8, color: '#6b7280',
          }}
        >
          <Upload size={20} />
          <span style={{ fontSize: 13, fontWeight: 500 }}>Click to upload or select image</span>
        </button>
      )}

      {/* URL input fallback */}
      <input
        className="npb-field__input"
        type="url"
        value={strVal}
        placeholder="https://... or use button above"
        onChange={(e) => onChange(e.target.value)}
        style={{ marginTop: 4, fontSize: 12 }}
      />
    </div>
  );
}
