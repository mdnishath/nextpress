import React, { useState, useCallback } from 'react';
import {
  DndContext,
  closestCenter,
  PointerSensor,
  useSensor,
  useSensors,
  DragEndEvent,
} from '@dnd-kit/core';
import {
  arrayMove,
  SortableContext,
  useSortable,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

/* ------------------------------------------------------------------ */
/*  Types                                                              */
/* ------------------------------------------------------------------ */

interface FieldOption {
  label: string;
  value: string;
}

interface SchemaField {
  key: string;
  label: string;
  type: string;
  default?: unknown;
  placeholder?: string;
  options?: FieldOption[];
  min?: number;
  max?: number;
  step?: number;
}

interface ContentSchema {
  fields: SchemaField[];
}

interface SchemaBuilderProps {
  schema: ContentSchema;
  onChange: (schema: ContentSchema) => void;
}

/* ------------------------------------------------------------------ */
/*  Constants                                                          */
/* ------------------------------------------------------------------ */

const FIELD_TYPES: { value: string; label: string; icon: string }[] = [
  { value: 'text', label: 'Text', icon: 'T' },
  { value: 'textarea', label: 'Text Area', icon: '¶' },
  { value: 'richtext', label: 'Rich Text', icon: '✎' },
  { value: 'number', label: 'Number', icon: '#' },
  { value: 'select', label: 'Dropdown', icon: '▼' },
  { value: 'boolean', label: 'Toggle', icon: '◉' },
  { value: 'color', label: 'Color', icon: '◆' },
  { value: 'image', label: 'Image', icon: '▣' },
  { value: 'url', label: 'URL', icon: '🔗' },
];

const DEFAULT_VALUES: Record<string, unknown> = {
  text: '',
  textarea: '',
  richtext: '',
  number: 0,
  select: '',
  boolean: false,
  color: '#000000',
  image: '',
  url: '',
};

/* ------------------------------------------------------------------ */
/*  Styles                                                             */
/* ------------------------------------------------------------------ */

const s = {
  container: { marginBottom: 8 } as React.CSSProperties,
  addBar: {
    display: 'flex', gap: 6, flexWrap: 'wrap' as const, padding: '12px 0',
  } as React.CSSProperties,
  addBtn: {
    display: 'flex', alignItems: 'center', gap: 4,
    padding: '6px 12px', background: '#f3f4f6', border: '1px solid #e5e7eb',
    borderRadius: 6, fontSize: 12, cursor: 'pointer', fontWeight: 500,
    color: '#374151', transition: 'all 0.15s',
  } as React.CSSProperties,
  addIcon: {
    width: 18, height: 18, display: 'flex', alignItems: 'center', justifyContent: 'center',
    fontSize: 12, fontWeight: 700,
  } as React.CSSProperties,
  fieldCard: {
    background: '#fff', border: '1px solid #e5e7eb', borderRadius: 8,
    marginBottom: 6, overflow: 'hidden', transition: 'box-shadow 0.15s',
  } as React.CSSProperties,
  fieldHeader: {
    display: 'flex', alignItems: 'center', gap: 8, padding: '8px 12px',
    cursor: 'pointer', userSelect: 'none' as const,
  } as React.CSSProperties,
  dragHandle: {
    cursor: 'grab', color: '#9ca3af', fontSize: 14, lineHeight: 1,
    display: 'flex', alignItems: 'center',
  } as React.CSSProperties,
  badge: {
    padding: '2px 6px', borderRadius: 4, fontSize: 10, fontWeight: 600,
    textTransform: 'uppercase' as const, background: '#ede9fe', color: '#7c3aed',
  } as React.CSSProperties,
  fieldLabel: {
    flex: 1, fontSize: 13, fontWeight: 600, color: '#374151',
  } as React.CSSProperties,
  deleteBtn: {
    padding: '2px 6px', background: 'none', border: 'none', color: '#9ca3af',
    cursor: 'pointer', fontSize: 16, lineHeight: 1, borderRadius: 4,
  } as React.CSSProperties,
  fieldBody: {
    padding: '0 12px 12px', borderTop: '1px solid #f3f4f6',
  } as React.CSSProperties,
  row: {
    display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8, marginTop: 8,
  } as React.CSSProperties,
  label: {
    display: 'block', fontSize: 11, fontWeight: 600, color: '#6b7280', marginBottom: 3,
  } as React.CSSProperties,
  input: {
    width: '100%', padding: '6px 8px', border: '1px solid #d1d5db', borderRadius: 5,
    fontSize: 13, boxSizing: 'border-box' as const,
  } as React.CSSProperties,
  select: {
    width: '100%', padding: '6px 8px', border: '1px solid #d1d5db', borderRadius: 5,
    fontSize: 13, boxSizing: 'border-box' as const, background: '#fff',
  } as React.CSSProperties,
  keyField: {
    width: '100%', padding: '6px 8px', border: '1px solid #d1d5db', borderRadius: 5,
    fontSize: 12, fontFamily: 'monospace', boxSizing: 'border-box' as const,
    background: '#f9fafb', color: '#6b7280',
  } as React.CSSProperties,
  optionRow: {
    display: 'flex', gap: 6, alignItems: 'center', marginTop: 4,
  } as React.CSSProperties,
  optionInput: {
    flex: 1, padding: '4px 6px', border: '1px solid #d1d5db', borderRadius: 4,
    fontSize: 12, boxSizing: 'border-box' as const,
  } as React.CSSProperties,
  optionDelete: {
    padding: '2px 6px', background: 'none', border: 'none', color: '#ef4444',
    cursor: 'pointer', fontSize: 14, borderRadius: 4,
  } as React.CSSProperties,
  addOptionBtn: {
    padding: '4px 8px', background: '#f3f4f6', border: '1px dashed #d1d5db',
    borderRadius: 4, fontSize: 11, cursor: 'pointer', marginTop: 4, color: '#6b7280',
  } as React.CSSProperties,
  emptyState: {
    textAlign: 'center' as const, padding: '24px 16px', color: '#9ca3af',
    fontSize: 13, border: '2px dashed #e5e7eb', borderRadius: 8,
  } as React.CSSProperties,
};

/* ------------------------------------------------------------------ */
/*  Sortable Field Card                                                */
/* ------------------------------------------------------------------ */

interface SortableFieldProps {
  field: SchemaField;
  index: number;
  expanded: boolean;
  onToggleExpand: () => void;
  onUpdate: (index: number, updates: Partial<SchemaField>) => void;
  onDelete: (index: number) => void;
}

function SortableFieldCard({ field, index, expanded, onToggleExpand, onUpdate, onDelete }: SortableFieldProps) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: field.key,
  });

  const style: React.CSSProperties = {
    ...s.fieldCard,
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.5 : 1,
    boxShadow: isDragging ? '0 4px 12px rgba(0,0,0,0.15)' : undefined,
  };

  const labelToKey = (label: string) =>
    label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');

  return (
    <div ref={setNodeRef} style={style}>
      <div style={s.fieldHeader} onClick={onToggleExpand}>
        <span style={s.dragHandle} {...attributes} {...listeners} onClick={(e) => e.stopPropagation()}>
          ⠿
        </span>
        <span style={s.badge}>{field.type}</span>
        <span style={s.fieldLabel}>{field.label || 'Untitled'}</span>
        <span style={{ fontSize: 11, color: '#9ca3af', fontFamily: 'monospace' }}>{field.key}</span>
        <button style={s.deleteBtn} onClick={(e) => { e.stopPropagation(); onDelete(index); }} title="Remove field">
          ×
        </button>
        <span style={{ fontSize: 10, color: '#9ca3af', marginLeft: 4 }}>
          {expanded ? '▲' : '▼'}
        </span>
      </div>

      {expanded && (
        <div style={s.fieldBody}>
          <div style={s.row}>
            <div>
              <label style={s.label}>Label</label>
              <input
                style={s.input}
                value={field.label}
                onChange={(e) => {
                  const newLabel = e.target.value;
                  const updates: Partial<SchemaField> = { label: newLabel };
                  // Auto-generate key from label if key matches old auto-generated key
                  const autoKey = labelToKey(field.label);
                  if (field.key === autoKey || !field.key) {
                    updates.key = labelToKey(newLabel);
                  }
                  onUpdate(index, updates);
                }}
                placeholder="Field Label"
              />
            </div>
            <div>
              <label style={s.label}>Key (auto-generated)</label>
              <input
                style={s.keyField}
                value={field.key}
                onChange={(e) => onUpdate(index, { key: e.target.value.toLowerCase().replace(/[^a-z0-9_]/g, '') })}
                placeholder="field_key"
              />
            </div>
          </div>
          <div style={s.row}>
            <div>
              <label style={s.label}>Type</label>
              <select
                style={s.select}
                value={field.type}
                onChange={(e) => onUpdate(index, { type: e.target.value, default: DEFAULT_VALUES[e.target.value] })}
              >
                {FIELD_TYPES.map((ft) => (
                  <option key={ft.value} value={ft.value}>{ft.label}</option>
                ))}
              </select>
            </div>
            <div>
              <label style={s.label}>Default Value</label>
              {field.type === 'boolean' ? (
                <select
                  style={s.select}
                  value={field.default ? 'true' : 'false'}
                  onChange={(e) => onUpdate(index, { default: e.target.value === 'true' })}
                >
                  <option value="false">Off</option>
                  <option value="true">On</option>
                </select>
              ) : field.type === 'color' ? (
                <div style={{ display: 'flex', gap: 4 }}>
                  <input
                    type="color"
                    value={(field.default as string) || '#000000'}
                    onChange={(e) => onUpdate(index, { default: e.target.value })}
                    style={{ width: 32, height: 30, padding: 0, border: '1px solid #d1d5db', borderRadius: 4, cursor: 'pointer' }}
                  />
                  <input
                    style={{ ...s.input, flex: 1 }}
                    value={(field.default as string) || ''}
                    onChange={(e) => onUpdate(index, { default: e.target.value })}
                    placeholder="#000000"
                  />
                </div>
              ) : field.type === 'number' ? (
                <input
                  type="number"
                  style={s.input}
                  value={(field.default as number) ?? 0}
                  onChange={(e) => onUpdate(index, { default: Number(e.target.value) })}
                />
              ) : (
                <input
                  style={s.input}
                  value={(field.default as string) || ''}
                  onChange={(e) => onUpdate(index, { default: e.target.value })}
                  placeholder="Default value"
                />
              )}
            </div>
          </div>

          {field.type === 'text' && (
            <div style={{ marginTop: 8 }}>
              <label style={s.label}>Placeholder</label>
              <input
                style={s.input}
                value={field.placeholder || ''}
                onChange={(e) => onUpdate(index, { placeholder: e.target.value })}
                placeholder="Placeholder text"
              />
            </div>
          )}

          {field.type === 'number' && (
            <div style={s.row}>
              <div>
                <label style={s.label}>Min</label>
                <input type="number" style={s.input} value={field.min ?? ''} onChange={(e) => onUpdate(index, { min: e.target.value ? Number(e.target.value) : undefined })} />
              </div>
              <div>
                <label style={s.label}>Max</label>
                <input type="number" style={s.input} value={field.max ?? ''} onChange={(e) => onUpdate(index, { max: e.target.value ? Number(e.target.value) : undefined })} />
              </div>
            </div>
          )}

          {field.type === 'select' && (
            <div style={{ marginTop: 8 }}>
              <label style={s.label}>Options</label>
              {(field.options || []).map((opt, oi) => (
                <div key={oi} style={s.optionRow}>
                  <input
                    style={s.optionInput}
                    value={opt.label}
                    onChange={(e) => {
                      const opts = [...(field.options || [])];
                      opts[oi] = { ...opts[oi], label: e.target.value };
                      if (opts[oi].value === labelToKey(opt.label) || !opts[oi].value) {
                        opts[oi].value = labelToKey(e.target.value);
                      }
                      onUpdate(index, { options: opts });
                    }}
                    placeholder="Label"
                  />
                  <input
                    style={{ ...s.optionInput, fontFamily: 'monospace', fontSize: 11 }}
                    value={opt.value}
                    onChange={(e) => {
                      const opts = [...(field.options || [])];
                      opts[oi] = { ...opts[oi], value: e.target.value };
                      onUpdate(index, { options: opts });
                    }}
                    placeholder="value"
                  />
                  <button
                    style={s.optionDelete}
                    onClick={() => {
                      const opts = (field.options || []).filter((_, i) => i !== oi);
                      onUpdate(index, { options: opts });
                    }}
                  >
                    ×
                  </button>
                </div>
              ))}
              <button
                style={s.addOptionBtn}
                onClick={() => {
                  const opts = [...(field.options || []), { label: '', value: '' }];
                  onUpdate(index, { options: opts });
                }}
              >
                + Add Option
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

/* ------------------------------------------------------------------ */
/*  Schema Builder                                                     */
/* ------------------------------------------------------------------ */

export function SchemaBuilder({ schema, onChange }: SchemaBuilderProps) {
  const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
  const [showJsonEditor, setShowJsonEditor] = useState(false);
  const [jsonText, setJsonText] = useState('');

  const fields = schema?.fields || [];

  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 5 } })
  );

  const updateField = useCallback((index: number, updates: Partial<SchemaField>) => {
    const newFields = [...fields];
    newFields[index] = { ...newFields[index], ...updates };
    onChange({ fields: newFields });
  }, [fields, onChange]);

  const deleteField = useCallback((index: number) => {
    const newFields = fields.filter((_, i) => i !== index);
    onChange({ fields: newFields });
    if (expandedIndex === index) setExpandedIndex(null);
    else if (expandedIndex !== null && expandedIndex > index) setExpandedIndex(expandedIndex - 1);
  }, [fields, onChange, expandedIndex]);

  const addField = useCallback((type: string) => {
    const count = fields.filter((f) => f.type === type).length;
    const label = `${FIELD_TYPES.find((t) => t.value === type)?.label || type} ${count + 1}`;
    const key = label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
    const newField: SchemaField = {
      key,
      label,
      type,
      default: DEFAULT_VALUES[type],
      ...(type === 'select' ? { options: [{ label: 'Option 1', value: 'option_1' }] } : {}),
    };
    onChange({ fields: [...fields, newField] });
    setExpandedIndex(fields.length);
  }, [fields, onChange]);

  const handleDragEnd = useCallback((event: DragEndEvent) => {
    const { active, over } = event;
    if (!over || active.id === over.id) return;
    const oldIndex = fields.findIndex((f) => f.key === active.id);
    const newIndex = fields.findIndex((f) => f.key === over.id);
    onChange({ fields: arrayMove(fields, oldIndex, newIndex) });
    if (expandedIndex === oldIndex) setExpandedIndex(newIndex);
    else if (expandedIndex === newIndex) setExpandedIndex(oldIndex);
  }, [fields, onChange, expandedIndex]);

  return (
    <div style={s.container}>
      {fields.length === 0 ? (
        <div style={s.emptyState}>
          No fields yet. Add fields below to build your component.
        </div>
      ) : (
        <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
          <SortableContext items={fields.map((f) => f.key)} strategy={verticalListSortingStrategy}>
            {fields.map((field, index) => (
              <SortableFieldCard
                key={field.key}
                field={field}
                index={index}
                expanded={expandedIndex === index}
                onToggleExpand={() => setExpandedIndex(expandedIndex === index ? null : index)}
                onUpdate={updateField}
                onDelete={deleteField}
              />
            ))}
          </SortableContext>
        </DndContext>
      )}

      {/* Add Field Buttons */}
      <div style={s.addBar}>
        {FIELD_TYPES.map((ft) => (
          <button
            key={ft.value}
            style={s.addBtn}
            onClick={() => addField(ft.value)}
            onMouseEnter={(e) => { (e.target as HTMLElement).style.borderColor = '#7c3aed'; }}
            onMouseLeave={(e) => { (e.target as HTMLElement).style.borderColor = '#e5e7eb'; }}
          >
            <span style={s.addIcon}>{ft.icon}</span>
            {ft.label}
          </button>
        ))}
      </div>

      {/* JSON Toggle */}
      <div style={{ borderTop: '1px solid #e5e7eb', paddingTop: 8, marginTop: 4 }}>
        <button
          style={{
            background: 'none', border: 'none', fontSize: 11, color: '#9ca3af',
            cursor: 'pointer', padding: 0, textDecoration: 'underline',
          }}
          onClick={() => {
            if (!showJsonEditor) {
              setJsonText(JSON.stringify(schema, null, 2));
            }
            setShowJsonEditor(!showJsonEditor);
          }}
        >
          {showJsonEditor ? 'Hide JSON' : 'Advanced: Edit JSON'}
        </button>
        {showJsonEditor && (
          <div style={{ marginTop: 8 }}>
            <textarea
              value={jsonText}
              onChange={(e) => setJsonText(e.target.value)}
              rows={8}
              style={{
                width: '100%', padding: '8px', border: '1px solid #d1d5db', borderRadius: 6,
                fontSize: 11, fontFamily: 'monospace', resize: 'vertical', boxSizing: 'border-box',
              }}
            />
            <button
              style={{
                padding: '4px 12px', background: '#7c3aed', color: '#fff', border: 'none',
                borderRadius: 4, fontSize: 11, fontWeight: 600, cursor: 'pointer', marginTop: 4,
              }}
              onClick={() => {
                try {
                  const parsed = JSON.parse(jsonText);
                  onChange(parsed);
                  setShowJsonEditor(false);
                } catch {
                  alert('Invalid JSON');
                }
              }}
            >
              Apply JSON
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
