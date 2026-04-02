import { useState } from '@wordpress/element';
import { ColorControl } from './ColorControl';
import type { SectionStyle } from '../../../types/builder';

interface BackgroundControlProps {
  style: SectionStyle;
  onChange: (updates: Partial<SectionStyle>) => void;
}

type BgTab = 'color' | 'gradient' | 'image';

export function BackgroundControl({ style, onChange }: BackgroundControlProps) {
  const [tab, setTab] = useState<BgTab>(
    style.backgroundImage ? 'image' : style.backgroundGradient ? 'gradient' : 'color'
  );

  const tabStyle = (t: BgTab) => ({
    flex: 1, padding: '6px 0', border: '1px solid #e5e7eb',
    borderRadius: 4, fontSize: 12, fontWeight: 600 as const, cursor: 'pointer' as const,
    background: tab === t ? '#ede9fe' : '#fff',
    color: tab === t ? '#7c3aed' : '#6b7280',
    borderColor: tab === t ? '#7c3aed' : '#e5e7eb',
  });

  return (
    <div className="npb-field">
      <label className="npb-field__label">Background</label>

      {/* Tab switcher */}
      <div style={{ display: 'flex', gap: 4, marginBottom: 10 }}>
        <button onClick={() => setTab('color')} style={tabStyle('color')}>Color</button>
        <button onClick={() => setTab('gradient')} style={tabStyle('gradient')}>Gradient</button>
        <button onClick={() => setTab('image')} style={tabStyle('image')}>Image</button>
      </div>

      {tab === 'color' && (
        <ColorControl
          label=""
          value={style.backgroundColor || ''}
          onChange={(val) => onChange({ backgroundColor: val })}
        />
      )}

      {tab === 'gradient' && (
        <div>
          <input
            className="npb-field__input"
            type="text"
            value={style.backgroundGradient || ''}
            onChange={(e) => onChange({ backgroundGradient: e.target.value })}
            placeholder="linear-gradient(135deg, #667eea, #764ba2)"
          />
          {/* Preview */}
          {style.backgroundGradient && (
            <div style={{
              height: 32, borderRadius: 6, marginTop: 8,
              background: style.backgroundGradient,
              border: '1px solid #e5e7eb',
            }} />
          )}
        </div>
      )}

      {tab === 'image' && (
        <div>
          <div style={{ marginBottom: 8 }}>
            <label style={{ fontSize: 11, color: '#6b7280', marginBottom: 3, display: 'block' }}>Image URL</label>
            <input
              className="npb-field__input"
              type="url"
              value={style.backgroundImage || ''}
              onChange={(e) => onChange({ backgroundImage: e.target.value })}
              placeholder="https://..."
            />
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8 }}>
            <div>
              <label style={{ fontSize: 11, color: '#6b7280', marginBottom: 3, display: 'block' }}>Size</label>
              <select
                className="npb-field__input"
                value={style.backgroundSize || 'cover'}
                onChange={(e) => onChange({ backgroundSize: e.target.value })}
              >
                <option value="cover">Cover</option>
                <option value="contain">Contain</option>
                <option value="auto">Auto</option>
                <option value="100% 100%">Stretch</option>
              </select>
            </div>
            <div>
              <label style={{ fontSize: 11, color: '#6b7280', marginBottom: 3, display: 'block' }}>Position</label>
              <select
                className="npb-field__input"
                value={style.backgroundPosition || 'center'}
                onChange={(e) => onChange({ backgroundPosition: e.target.value })}
              >
                <option value="center">Center</option>
                <option value="top">Top</option>
                <option value="bottom">Bottom</option>
                <option value="left">Left</option>
                <option value="right">Right</option>
              </select>
            </div>
          </div>

          {/* Overlay */}
          <div style={{ marginTop: 10 }}>
            <label style={{ fontSize: 11, color: '#6b7280', marginBottom: 3, display: 'block' }}>Overlay</label>
            <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
              <input
                type="color"
                value={style.backgroundOverlayColor || '#000000'}
                onChange={(e) => onChange({ backgroundOverlayColor: e.target.value })}
                style={{ width: 32, height: 32, padding: 1, cursor: 'pointer' }}
              />
              <input
                className="npb-field__input"
                type="number"
                value={style.backgroundOverlayOpacity ?? ''}
                onChange={(e) => onChange({ backgroundOverlayOpacity: Number(e.target.value) })}
                placeholder="50"
                min={0}
                max={100}
                style={{ flex: 1 }}
              />
              <span style={{ fontSize: 12, color: '#6b7280' }}>%</span>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
