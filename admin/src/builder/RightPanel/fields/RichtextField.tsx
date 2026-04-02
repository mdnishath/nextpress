import { useState } from '@wordpress/element';
import type { ContentField } from '../../../types/builder';

interface FieldProps {
  field: ContentField;
  value: unknown;
  onChange: (value: unknown) => void;
}

/**
 * Rich text field with basic formatting toolbar.
 * Uses a contentEditable div with toolbar buttons for bold, italic, link.
 */
export function RichtextField({ field, value, onChange }: FieldProps) {
  const [showHtml, setShowHtml] = useState(false);
  const strVal = typeof value === 'string' ? value : '';

  if (showHtml) {
    return (
      <div className="npb-field">
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6 }}>
          <label className="npb-field__label" style={{ marginBottom: 0 }}>{field.label}</label>
          <button
            onClick={() => setShowHtml(false)}
            style={{ fontSize: 11, color: '#7c3aed', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 600 }}
          >
            Visual
          </button>
        </div>
        <textarea
          className="npb-field__input"
          rows={6}
          value={strVal}
          onChange={(e) => onChange(e.target.value)}
          style={{ fontFamily: 'var(--npb-font-mono)', fontSize: 12, resize: 'vertical' }}
        />
      </div>
    );
  }

  return (
    <div className="npb-field">
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6 }}>
        <label className="npb-field__label" style={{ marginBottom: 0 }}>{field.label}</label>
        <button
          onClick={() => setShowHtml(true)}
          style={{ fontSize: 11, color: '#7c3aed', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 600 }}
        >
          HTML
        </button>
      </div>
      {/* Formatting toolbar */}
      <div style={{
        display: 'flex', gap: 2, padding: '4px 8px',
        border: '1px solid var(--npb-border)', borderBottom: 'none',
        borderRadius: 'var(--npb-radius-sm) var(--npb-radius-sm) 0 0',
        background: '#fafafa',
      }}>
        {[
          { cmd: 'bold', label: 'B', style: { fontWeight: 700 } },
          { cmd: 'italic', label: 'I', style: { fontStyle: 'italic' } },
          { cmd: 'underline', label: 'U', style: { textDecoration: 'underline' } },
          { cmd: 'strikeThrough', label: 'S', style: { textDecoration: 'line-through' } },
        ].map((btn) => (
          <button
            key={btn.cmd}
            onMouseDown={(e) => {
              e.preventDefault();
              document.execCommand(btn.cmd);
            }}
            style={{
              width: 28, height: 28, border: 'none', background: 'transparent',
              cursor: 'pointer', fontSize: 13, borderRadius: 4, ...btn.style,
            }}
          >
            {btn.label}
          </button>
        ))}
      </div>
      <div
        contentEditable
        className="npb-field__input"
        style={{
          minHeight: 80, borderTopLeftRadius: 0, borderTopRightRadius: 0,
          lineHeight: 1.6, outline: 'none',
        }}
        dangerouslySetInnerHTML={{ __html: strVal }}
        onBlur={(e) => onChange(e.currentTarget.innerHTML)}
      />
    </div>
  );
}
