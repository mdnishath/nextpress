import { useState } from '@wordpress/element';
import { Plus, Trash2, GripVertical, ChevronDown, ChevronRight } from 'lucide-react';
import type { ContentField } from '../../../types/builder';

interface FieldProps {
  field: ContentField;
  value: unknown;
  onChange: (value: unknown) => void;
}

// Import the FieldRenderer from ContentEditor to render sub-fields recursively
// This creates a circular dependency, so we inline a simple version here
function SimpleFieldRenderer({ field, value, onChange }: { field: ContentField; value: unknown; onChange: (v: unknown) => void }) {
  const strVal = typeof value === 'string' ? value : (value != null ? String(value) : '');

  switch (field.type) {
    case 'text':
    case 'url':
      return (
        <div style={{ marginBottom: 8 }}>
          <label style={{ fontSize: 11, fontWeight: 600, color: '#6b7280', display: 'block', marginBottom: 3 }}>{field.label}</label>
          <input
            className="npb-field__input"
            type={field.type === 'url' ? 'url' : 'text'}
            value={strVal}
            placeholder={field.placeholder}
            onChange={(e) => onChange(e.target.value)}
          />
        </div>
      );
    case 'textarea':
      return (
        <div style={{ marginBottom: 8 }}>
          <label style={{ fontSize: 11, fontWeight: 600, color: '#6b7280', display: 'block', marginBottom: 3 }}>{field.label}</label>
          <textarea
            className="npb-field__input"
            rows={2}
            value={strVal}
            placeholder={field.placeholder}
            onChange={(e) => onChange(e.target.value)}
            style={{ resize: 'vertical' }}
          />
        </div>
      );
    case 'image':
      return (
        <div style={{ marginBottom: 8 }}>
          <label style={{ fontSize: 11, fontWeight: 600, color: '#6b7280', display: 'block', marginBottom: 3 }}>{field.label}</label>
          <input
            className="npb-field__input"
            type="url"
            value={strVal}
            placeholder="Image URL"
            onChange={(e) => onChange(e.target.value)}
          />
        </div>
      );
    case 'number':
      return (
        <div style={{ marginBottom: 8 }}>
          <label style={{ fontSize: 11, fontWeight: 600, color: '#6b7280', display: 'block', marginBottom: 3 }}>{field.label}</label>
          <input
            className="npb-field__input"
            type="number"
            value={typeof value === 'number' ? value : ''}
            onChange={(e) => onChange(Number(e.target.value))}
          />
        </div>
      );
    default:
      return (
        <div style={{ marginBottom: 8 }}>
          <label style={{ fontSize: 11, fontWeight: 600, color: '#6b7280', display: 'block', marginBottom: 3 }}>{field.label}</label>
          <input
            className="npb-field__input"
            type="text"
            value={strVal}
            onChange={(e) => onChange(e.target.value)}
          />
        </div>
      );
  }
}

export function RepeaterField({ field, value, onChange }: FieldProps) {
  const items = Array.isArray(value) ? value : [];
  const subFields = Array.isArray(field.fields) ? field.fields : [];
  const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

  const addItem = () => {
    const newItem: Record<string, unknown> = {};
    subFields.forEach((f) => { newItem[f.key] = f.default ?? ''; });
    onChange([...items, newItem]);
    setExpandedIndex(items.length);
  };

  const removeItem = (index: number) => {
    onChange(items.filter((_: unknown, i: number) => i !== index));
    if (expandedIndex === index) setExpandedIndex(null);
  };

  const updateItem = (index: number, key: string, val: unknown) => {
    const updated = items.map((item: Record<string, unknown>, i: number) =>
      i === index ? { ...item, [key]: val } : item
    );
    onChange(updated);
  };

  const moveItem = (from: number, to: number) => {
    if (to < 0 || to >= items.length) return;
    const updated = [...items];
    const [moved] = updated.splice(from, 1);
    updated.splice(to, 0, moved);
    onChange(updated);
    setExpandedIndex(to);
  };

  // Get a preview label for collapsed items
  const getItemLabel = (item: Record<string, unknown>, index: number): string => {
    const firstTextField = subFields.find((f) => f.type === 'text');
    if (firstTextField && item[firstTextField.key]) {
      const val = String(item[firstTextField.key]);
      return val.length > 30 ? val.slice(0, 30) + '...' : val;
    }
    return `Item ${index + 1}`;
  };

  return (
    <div className="npb-field">
      <label className="npb-field__label">{field.label} ({items.length})</label>

      <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
        {items.map((item: Record<string, unknown>, index: number) => {
          const isExpanded = expandedIndex === index;
          return (
            <div
              key={index}
              style={{
                border: '1px solid #e5e7eb', borderRadius: 8,
                background: isExpanded ? '#fafafa' : '#fff',
                overflow: 'hidden',
              }}
            >
              {/* Item header */}
              <div
                style={{
                  display: 'flex', alignItems: 'center', gap: 6,
                  padding: '8px 10px', cursor: 'pointer',
                }}
                onClick={() => setExpandedIndex(isExpanded ? null : index)}
              >
                <GripVertical size={14} style={{ color: '#a1a1aa', flexShrink: 0 }} />
                {isExpanded ? <ChevronDown size={14} /> : <ChevronRight size={14} />}
                <span style={{ flex: 1, fontSize: 13, fontWeight: 500, color: '#374151' }}>
                  {getItemLabel(item, index)}
                </span>
                <button
                  onClick={(e) => { e.stopPropagation(); moveItem(index, index - 1); }}
                  disabled={index === 0}
                  style={{ background: 'none', border: 'none', cursor: 'pointer', fontSize: 14, color: '#a1a1aa', padding: 2 }}
                  title="Move up"
                >
                  ↑
                </button>
                <button
                  onClick={(e) => { e.stopPropagation(); moveItem(index, index + 1); }}
                  disabled={index === items.length - 1}
                  style={{ background: 'none', border: 'none', cursor: 'pointer', fontSize: 14, color: '#a1a1aa', padding: 2 }}
                  title="Move down"
                >
                  ↓
                </button>
                <button
                  onClick={(e) => { e.stopPropagation(); removeItem(index); }}
                  style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#ef4444', padding: 2 }}
                  title="Remove"
                >
                  <Trash2 size={14} />
                </button>
              </div>

              {/* Expanded sub-fields */}
              {isExpanded && (
                <div style={{ padding: '8px 12px 12px', borderTop: '1px solid #e5e7eb' }}>
                  {subFields.map((subField) => (
                    <SimpleFieldRenderer
                      key={subField.key}
                      field={subField}
                      value={item[subField.key]}
                      onChange={(val) => updateItem(index, subField.key, val)}
                    />
                  ))}
                </div>
              )}
            </div>
          );
        })}
      </div>

      {/* Add button */}
      <button
        onClick={addItem}
        style={{
          width: '100%', padding: '8px 12px', marginTop: 8,
          border: '1px dashed #d1d5db', borderRadius: 6,
          background: 'transparent', cursor: 'pointer',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          gap: 6, color: '#6b7280', fontSize: 13, fontWeight: 500,
        }}
      >
        <Plus size={14} />
        Add {field.label?.replace(/s$/, '') || 'Item'}
      </button>
    </div>
  );
}
