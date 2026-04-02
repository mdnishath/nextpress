import { useState, useMemo } from '@wordpress/element';
import * as icons from 'lucide-react';
import type { ContentField } from '../../../types/builder';

interface FieldProps {
  field: ContentField;
  value: unknown;
  onChange: (value: unknown) => void;
}

// Get all icon names from lucide-react (filter out non-icon exports)
const ALL_ICONS = Object.keys(icons).filter(
  (key) => key !== 'default' && key !== 'createLucideIcon' && key[0] === key[0].toUpperCase()
);

export function IconField({ field, value, onChange }: FieldProps) {
  const strVal = typeof value === 'string' ? value : '';
  const [search, setSearch] = useState('');
  const [showPicker, setShowPicker] = useState(false);

  const filtered = useMemo(() => {
    if (!search.trim()) return ALL_ICONS.slice(0, 60);
    const q = search.toLowerCase();
    return ALL_ICONS.filter((name) => name.toLowerCase().includes(q)).slice(0, 60);
  }, [search]);

  const SelectedIcon = strVal ? (icons as Record<string, icons.LucideIcon>)[strVal] : null;

  return (
    <div className="npb-field">
      <label className="npb-field__label">{field.label}</label>

      {/* Current selection */}
      <div style={{ display: 'flex', gap: 8, alignItems: 'center', marginBottom: 8 }}>
        <div
          onClick={() => setShowPicker(!showPicker)}
          style={{
            width: 40, height: 40, borderRadius: 8,
            border: '1px solid #e5e7eb', background: '#fafafa',
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            cursor: 'pointer',
          }}
        >
          {SelectedIcon ? <SelectedIcon size={20} /> : <span style={{ color: '#a1a1aa', fontSize: 11 }}>None</span>}
        </div>
        <input
          className="npb-field__input"
          type="text"
          value={strVal}
          placeholder="Click to pick icon"
          readOnly
          onClick={() => setShowPicker(!showPicker)}
          style={{ flex: 1, cursor: 'pointer' }}
        />
        {strVal && (
          <button
            onClick={() => onChange('')}
            style={{ fontSize: 11, color: '#ef4444', background: 'none', border: 'none', cursor: 'pointer' }}
          >
            Clear
          </button>
        )}
      </div>

      {/* Icon picker dropdown */}
      {showPicker && (
        <div style={{
          border: '1px solid #e5e7eb', borderRadius: 8,
          background: '#fff', padding: 8, maxHeight: 280, overflow: 'hidden',
          display: 'flex', flexDirection: 'column',
        }}>
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search icons..."
            autoFocus
            style={{
              width: '100%', padding: '6px 10px', border: '1px solid #e5e7eb',
              borderRadius: 6, fontSize: 13, marginBottom: 8, outline: 'none',
            }}
          />
          <div style={{
            display: 'grid', gridTemplateColumns: 'repeat(6, 1fr)', gap: 4,
            overflowY: 'auto', maxHeight: 200,
          }}>
            {filtered.map((name) => {
              const Icon = (icons as Record<string, icons.LucideIcon>)[name];
              if (!Icon) return null;
              return (
                <button
                  key={name}
                  onClick={() => { onChange(name); setShowPicker(false); setSearch(''); }}
                  title={name}
                  style={{
                    width: '100%', aspectRatio: '1', border: 'none',
                    borderRadius: 6, cursor: 'pointer',
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    background: strVal === name ? '#ede9fe' : 'transparent',
                    color: strVal === name ? '#7c3aed' : '#374151',
                  }}
                >
                  <Icon size={18} />
                </button>
              );
            })}
          </div>
          {filtered.length === 0 && (
            <div style={{ padding: 16, textAlign: 'center', color: '#a1a1aa', fontSize: 12 }}>
              No icons found
            </div>
          )}
        </div>
      )}
    </div>
  );
}
