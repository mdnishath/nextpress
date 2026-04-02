import { useState, useCallback } from '@wordpress/element';
import { Paintbrush, Image as ImageIcon, Film, Ban, Link2, Unlink2, Pencil, AlignLeft, AlignCenter, AlignRight, AlignJustify } from 'lucide-react';
import { useBuilderStore } from '../../store/builderStore';
import { getValueForBreakpoint } from '../../utils/responsive';
import { parseValueWithUnit, formatValueWithUnit } from './controls/UnitSwitcher';
import { MediaPicker } from './controls/MediaPicker';
import { CONTAINER_TYPES } from '../../utils/constants';
import type { Section, SectionStyle } from '../../types/builder';

interface StyleEditorProps {
  section: Section;
}

type BgType = 'none' | 'classic' | 'gradient' | 'image';

// ─── Shared sub-components ───

function SectionHeader({ title, open, onToggle }: { title: string; open: boolean; onToggle: () => void }) {
  return (
    <button type="button" className="el-section__header el-section__header--collapsible" onClick={onToggle}>
      <span className="el-section__arrow">{open ? '▼' : '▶'}</span>
      <span className="el-section__title">{title}</span>
    </button>
  );
}

function HoverTabBar({ active, onChange }: { active: 'normal' | 'hover'; onChange: (t: 'normal' | 'hover') => void }) {
  return (
    <div className="el-hover-tabs">
      <button
        type="button"
        className={`el-hover-tabs__btn ${active === 'normal' ? 'el-hover-tabs__btn--active' : ''}`}
        onClick={() => onChange('normal')}
      >Normal</button>
      <button
        type="button"
        className={`el-hover-tabs__btn ${active === 'hover' ? 'el-hover-tabs__btn--active' : ''}`}
        onClick={() => onChange('hover')}
      >Hover</button>
    </div>
  );
}

/** Parse gradient string into components */
function parseGradient(val: string) {
  const isRadial = val.startsWith('radial');
  const angleMatch = val.match(/(\d+)deg/);
  const colorMatches = val.match(/#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)/g);
  const locMatch1 = val.match(/(#[0-9a-fA-F]+|rgba?\([^)]+\))\s+(\d+)%/);
  const locMatch2 = val.match(/(#[0-9a-fA-F]+|rgba?\([^)]+\))\s+(\d+)%.*?(#[0-9a-fA-F]+|rgba?\([^)]+\))\s+(\d+)%/);
  return {
    type: isRadial ? 'radial' as const : 'linear' as const,
    angle: angleMatch ? parseInt(angleMatch[1]) : 180,
    color1: colorMatches?.[0] || '#000000',
    color2: colorMatches?.[1] || '#ffffff',
    loc1: locMatch1 ? parseInt(locMatch1[2]) : 0,
    loc2: locMatch2 ? parseInt(locMatch2[4]) : 100,
  };
}

function buildGradient(type: string, angle: number, c1: string, loc1: number, c2: string, loc2: number) {
  if (type === 'radial') return `radial-gradient(circle, ${c1} ${loc1}%, ${c2} ${loc2}%)`;
  return `linear-gradient(${angle}deg, ${c1} ${loc1}%, ${c2} ${loc2}%)`;
}

function GradientControls({ value, onChange }: { value: string; onChange: (v: string) => void }) {
  const parsed = parseGradient(value || 'linear-gradient(180deg, #000000 0%, #ffffff 100%)');
  const [color1, setColor1] = useState(parsed.color1);
  const [color2, setColor2] = useState(parsed.color2);
  const [loc1, setLoc1] = useState(parsed.loc1);
  const [loc2, setLoc2] = useState(parsed.loc2);
  const [gradType, setGradType] = useState(parsed.type);
  const [angle, setAngle] = useState(parsed.angle);

  const update = useCallback((c1: string, l1: number, c2: string, l2: number, t: string, a: number) => {
    onChange(buildGradient(t, a, c1, l1, c2, l2));
  }, [onChange]);

  return (
    <div>
      {/* Hint */}
      <div style={{
        background: 'rgba(255,255,255,0.06)', borderRadius: 4, padding: '8px 10px',
        fontSize: 11, color: 'rgba(255,255,255,0.45)', fontStyle: 'italic', marginBottom: 12, lineHeight: 1.4,
      }}>
        Set locations and angle for each breakpoint to ensure the gradient adapts to different screen sizes.
      </div>

      {/* Color 1 */}
      <div className="el-control">
        <span className="el-control__label">Color</span>
        <div className="el-color-input">
          <div className="el-color-input__swatch" style={{ background: color1 }}>
            <input type="color" value={color1} onChange={(e) => { setColor1(e.target.value); update(e.target.value, loc1, color2, loc2, gradType, angle); }} className="el-color-input__native" />
          </div>
          <input className="el-input el-input--full" value={color1} onChange={(e) => { setColor1(e.target.value); update(e.target.value, loc1, color2, loc2, gradType, angle); }} />
        </div>
      </div>

      {/* Location 1 */}
      <div className="el-control">
        <div className="el-control__header">
          <span className="el-control__label">Location</span>
          <span className="el-unit-btn">%</span>
        </div>
        <div className="el-slider-input">
          <input type="range" className="el-slider-input__range" min={0} max={100} value={loc1}
            onChange={(e) => { const v = parseInt(e.target.value); setLoc1(v); update(color1, v, color2, loc2, gradType, angle); }} />
          <input type="number" className="el-slider-input__num" value={loc1} min={0} max={100}
            onChange={(e) => { const v = parseInt(e.target.value) || 0; setLoc1(v); update(color1, v, color2, loc2, gradType, angle); }} />
        </div>
      </div>

      {/* Color 2 */}
      <div className="el-control">
        <span className="el-control__label">Second Color</span>
        <div className="el-color-input">
          <div className="el-color-input__swatch" style={{ background: color2 }}>
            <input type="color" value={color2} onChange={(e) => { setColor2(e.target.value); update(color1, loc1, e.target.value, loc2, gradType, angle); }} className="el-color-input__native" />
          </div>
          <input className="el-input el-input--full" value={color2} onChange={(e) => { setColor2(e.target.value); update(color1, loc1, e.target.value, loc2, gradType, angle); }} />
        </div>
      </div>

      {/* Location 2 */}
      <div className="el-control">
        <div className="el-control__header">
          <span className="el-control__label">Location</span>
          <span className="el-unit-btn">%</span>
        </div>
        <div className="el-slider-input">
          <input type="range" className="el-slider-input__range" min={0} max={100} value={loc2}
            onChange={(e) => { const v = parseInt(e.target.value); setLoc2(v); update(color1, loc1, color2, v, gradType, angle); }} />
          <input type="number" className="el-slider-input__num" value={loc2} min={0} max={100}
            onChange={(e) => { const v = parseInt(e.target.value) || 0; setLoc2(v); update(color1, loc1, color2, v, gradType, angle); }} />
        </div>
      </div>

      {/* Type */}
      <div className="el-control el-control--row">
        <span className="el-control__label">Type</span>
        <select className="el-select" value={gradType} onChange={(e) => { setGradType(e.target.value as 'linear' | 'radial'); update(color1, loc1, color2, loc2, e.target.value, angle); }}>
          <option value="linear">Linear</option>
          <option value="radial">Radial</option>
        </select>
      </div>

      {/* Angle — linear only */}
      {gradType === 'linear' && (
        <div className="el-control">
          <div className="el-control__header">
            <span className="el-control__label">Angle</span>
            <span className="el-unit-btn">deg</span>
          </div>
          <div className="el-slider-input">
            <input type="range" className="el-slider-input__range" min={0} max={360} value={angle}
              onChange={(e) => { const v = parseInt(e.target.value); setAngle(v); update(color1, loc1, color2, loc2, gradType, v); }} />
            <input type="number" className="el-slider-input__num" value={angle} min={0} max={360}
              onChange={(e) => { const v = parseInt(e.target.value) || 0; setAngle(v); update(color1, loc1, color2, loc2, gradType, v); }} />
          </div>
        </div>
      )}

      {/* Preview */}
      <div style={{
        height: 40, borderRadius: 6, marginTop: 8,
        background: buildGradient(gradType, angle, color1, loc1, color2, loc2),
        border: '1px solid rgba(255,255,255,0.12)',
      }} />
    </div>
  );
}

function ColorInput({ value, onChange, label }: { value: string; onChange: (v: string) => void; label?: string }) {
  return (
    <div className="el-control">
      {label && <span className="el-control__label">{label}</span>}
      <div className="el-color-input">
        <div
          className="el-color-input__swatch"
          style={{ background: value || 'transparent' }}
        >
          <input
            type="color"
            value={value || '#000000'}
            onChange={(e) => onChange(e.target.value)}
            className="el-color-input__native"
          />
        </div>
        <input
          type="text"
          className="el-input el-input--full"
          value={value}
          onChange={(e) => onChange(e.target.value)}
          placeholder="transparent"
        />
        {value && (
          <button type="button" className="el-clear-btn" onClick={() => onChange('')}>×</button>
        )}
      </div>
    </div>
  );
}

export function StyleEditor({ section }: StyleEditorProps) {
  const { updateStyle, updateResponsiveStyle, breakpoint } = useBuilderStore();
  const style = section.style;

  /** Update style per-device (responsive) */
  const setStyle = (key: string, val: string) => {
    updateResponsiveStyle(section.id, key, breakpoint, val);
  };

  /** Update multiple style properties per-device */
  const handleChange = (updates: Partial<SectionStyle>) => {
    for (const [key, val] of Object.entries(updates)) {
      if (typeof val === 'string') {
        updateResponsiveStyle(section.id, key, breakpoint, val);
      } else if (typeof val === 'number') {
        updateResponsiveStyle(section.id, key, breakpoint, String(val));
      } else {
        updateStyle(section.id, { [key]: val });
      }
    }
  };

  /** Read style value for current breakpoint */
  const getStyle = (key: string): string => {
    return getValueForBreakpoint(style[key], breakpoint);
  };

  // State
  const isContainer = CONTAINER_TYPES.includes(section.section_type as typeof CONTAINER_TYPES[number]);
  const [typoOpen, setTypoOpen] = useState(!isContainer);
  const [typoHover, setTypoHover] = useState<'normal' | 'hover'>('normal');
  const [bgOpen, setBgOpen] = useState(true);
  const [overlayOpen, setOverlayOpen] = useState(false);
  const [borderOpen, setBorderOpen] = useState(false);
  const [bgHover, setBgHover] = useState<'normal' | 'hover'>('normal');
  const [borderHover, setBorderHover] = useState<'normal' | 'hover'>('normal');
  const [radiusLinked, setRadiusLinked] = useState(true);

  // BG type
  const getBgType = (): BgType => {
    if (getStyle('backgroundImage')) return 'image';
    if (getStyle('backgroundGradient')) return 'gradient';
    if (getStyle('backgroundColor')) return 'classic';
    return 'none';
  };
  const [bgType, setBgType] = useState<BgType>(getBgType());

  // Gradient
  const [gradAngle, setGradAngle] = useState(135);

  const borderRadiusVal = getValueForBreakpoint(style.borderRadius, breakpoint);

  return (
    <div>
      {/* ▼ Typography (non-containers only) */}
      {!isContainer && (
        <div className="el-section">
          <SectionHeader title="Typography" open={typoOpen} onToggle={() => setTypoOpen(!typoOpen)} />
          {typoOpen && (
            <div className="el-section__body">
              {/* Alignment */}
              <div className="el-control el-control--row">
                <span className="el-control__label">Alignment</span>
                <div className="el-icon-group">
                  {[
                    { val: 'left', icon: <AlignLeft size={14} />, tip: 'Left' },
                    { val: 'center', icon: <AlignCenter size={14} />, tip: 'Center' },
                    { val: 'right', icon: <AlignRight size={14} />, tip: 'Right' },
                    { val: 'justify', icon: <AlignJustify size={14} />, tip: 'Justify' },
                  ].map((o) => (
                    <button
                      key={o.val}
                      type="button"
                      className={`el-icon-group__btn ${getStyle('textAlign') === o.val ? 'el-icon-group__btn--active' : ''}`}
                      onClick={() => handleChange({ textAlign: o.val })}
                      title={o.tip}
                    >
                      {o.icon}
                    </button>
                  ))}
                </div>
              </div>

              {/* Font Family */}
              <div className="el-control el-control--row">
                <span className="el-control__label">Font Family</span>
                <input
                  className="el-input"
                  style={{ width: 140 }}
                  value={getStyle('fontFamily')}
                  onChange={(e) => handleChange({ fontFamily: e.target.value })}
                  placeholder="Default"
                />
              </div>

              {/* Font Size */}
              <div className="el-control el-control--row">
                <span className="el-control__label">Size</span>
                <input
                  className="el-input"
                  style={{ width: 80 }}
                  value={getStyle('fontSize')}
                  onChange={(e) => handleChange({ fontSize: e.target.value })}
                  placeholder="16px"
                />
              </div>

              {/* Font Weight */}
              <div className="el-control el-control--row">
                <span className="el-control__label">Weight</span>
                <select
                  className="el-select"
                  value={getStyle('fontWeight') || ''}
                  onChange={(e) => handleChange({ fontWeight: e.target.value })}
                >
                  <option value="">Default</option>
                  <option value="100">100 - Thin</option>
                  <option value="200">200 - Extra Light</option>
                  <option value="300">300 - Light</option>
                  <option value="400">400 - Normal</option>
                  <option value="500">500 - Medium</option>
                  <option value="600">600 - Semi Bold</option>
                  <option value="700">700 - Bold</option>
                  <option value="800">800 - Extra Bold</option>
                  <option value="900">900 - Black</option>
                </select>
              </div>

              {/* Line Height */}
              <div className="el-control el-control--row">
                <span className="el-control__label">Line Height</span>
                <input
                  className="el-input"
                  style={{ width: 80 }}
                  value={getStyle('lineHeight')}
                  onChange={(e) => handleChange({ lineHeight: e.target.value })}
                  placeholder="1.5"
                />
              </div>

              {/* Normal / Hover tabs */}
              <HoverTabBar active={typoHover} onChange={setTypoHover} />

              {/* Text Color */}
              <div className="el-control el-control--row" style={{ marginTop: 12 }}>
                <span className="el-control__label">Text Color</span>
                <div className="el-color-input">
                  <div
                    className="el-color-input__swatch"
                    style={{ background: typoHover === 'normal' ? getStyle('textColor') || '' : getStyle('hoverColor') || '' }}
                  >
                    <input
                      type="color"
                      value={(typoHover === 'normal' ? getStyle('textColor') : getStyle('hoverColor')) || '#000000'}
                      onChange={(e) => {
                        if (typoHover === 'normal') handleChange({ textColor: e.target.value });
                        else handleChange({ hoverColor: e.target.value });
                      }}
                      className="el-color-input__native"
                    />
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
      )}

      {/* ▼ Background */}
      <div className="el-section">
        <SectionHeader title="Background" open={bgOpen} onToggle={() => setBgOpen(!bgOpen)} />
        {bgOpen && (
          <div className="el-section__body">
            <HoverTabBar active={bgHover} onChange={setBgHover} />

            {bgHover === 'normal' ? (
              <>
                <div className="el-control el-control--row">
                  <span className="el-control__label">Background Type</span>
                  <div className="el-icon-group">
                    {([
                      { val: 'none', icon: <Ban size={13} />, tip: 'None' },
                      { val: 'classic', icon: <Paintbrush size={13} />, tip: 'Classic' },
                      { val: 'gradient', icon: <span style={{ fontSize: 11 }}>⬡</span>, tip: 'Gradient' },
                      { val: 'image', icon: <ImageIcon size={13} />, tip: 'Image' },
                    ] as { val: BgType; icon: React.ReactNode; tip: string }[]).map((o) => (
                      <button
                        key={o.val}
                        type="button"
                        className={`el-icon-group__btn ${bgType === o.val ? 'el-icon-group__btn--active' : ''}`}
                        onClick={() => {
                          setBgType(o.val);
                          if (o.val === 'none') handleChange({ backgroundColor: '', backgroundGradient: '', backgroundImage: '' });
                        }}
                        title={o.tip}
                      >
                        {o.icon}
                      </button>
                    ))}
                  </div>
                </div>

                {bgType === 'classic' && (
                  <ColorInput label="Color" value={getStyle('backgroundColor')} onChange={(v) => handleChange({ backgroundColor: v })} />
                )}

                {bgType === 'gradient' && (
                  <GradientControls
                    value={getStyle('backgroundGradient')}
                    onChange={(v) => handleChange({ backgroundGradient: v })}
                  />
                )}

                {bgType === 'image' && (
                  <>
                    <MediaPicker
                      label="Image"
                      value={getStyle('backgroundImage')}
                      onChange={(url) => handleChange({ backgroundImage: url })}
                    />
                    <div className="el-control el-control--row">
                      <span className="el-control__label">Size</span>
                      <select className="el-select" value={getStyle('backgroundSize') || 'cover'} onChange={(e) => handleChange({ backgroundSize: e.target.value })}>
                        <option value="cover">Cover</option>
                        <option value="contain">Contain</option>
                        <option value="auto">Auto</option>
                      </select>
                    </div>
                    <div className="el-control el-control--row">
                      <span className="el-control__label">Position</span>
                      <select className="el-select" value={getStyle('backgroundPosition') || 'center'} onChange={(e) => handleChange({ backgroundPosition: e.target.value })}>
                        <option value="center">Center</option>
                        <option value="top">Top</option>
                        <option value="bottom">Bottom</option>
                      </select>
                    </div>
                  </>
                )}
              </>
            ) : (
              /* Hover state — bg color + gradient */
              <>
                <ColorInput label="Background Color" value={getStyle('hoverBackgroundColor')} onChange={(v) => handleChange({ hoverBackgroundColor: v })} />
                <GradientControls
                  value={getStyle('hoverBackgroundGradient')}
                  onChange={(v) => handleChange({ hoverBackgroundGradient: v })}
                />
              </>
            )}
          </div>
        )}
      </div>

      {/* ▶ Background Overlay */}
      <div className="el-section">
        <SectionHeader title="Background Overlay" open={overlayOpen} onToggle={() => setOverlayOpen(!overlayOpen)} />
        {overlayOpen && (
          <div className="el-section__body">
            <ColorInput label="Overlay Color" value={getStyle('backgroundOverlayColor')} onChange={(v) => handleChange({ backgroundOverlayColor: v })} />
            <div className="el-control el-control--row">
              <span className="el-control__label">Opacity</span>
              <input
                type="number"
                className="el-input"
                style={{ width: 60 }}
                value={getStyle('backgroundOverlayOpacity') || ''}
                onChange={(e) => handleChange({ backgroundOverlayOpacity: parseInt(e.target.value) || 0 })}
                min={0} max={100} placeholder="50"
              />
            </div>
          </div>
        )}
      </div>

      {/* ▶ Border */}
      <div className="el-section">
        <SectionHeader title="Border" open={borderOpen} onToggle={() => setBorderOpen(!borderOpen)} />
        {borderOpen && (
          <div className="el-section__body">
            <HoverTabBar active={borderHover} onChange={setBorderHover} />

            {borderHover === 'normal' ? (
              <>
                {/* Border Type */}
                <div className="el-control el-control--row">
                  <span className="el-control__label">Border Type</span>
                  <select className="el-select" value={getStyle('borderStyle')} onChange={(e) => handleChange({ borderStyle: e.target.value })}>
                    <option value="">Default</option>
                    <option value="none">None</option>
                    <option value="solid">Solid</option>
                    <option value="dashed">Dashed</option>
                    <option value="dotted">Dotted</option>
                    <option value="double">Double</option>
                  </select>
                </div>

                {/* Border Color */}
                {getStyle('borderStyle') && getStyle('borderStyle') !== 'none' && (
                  <>
                    <div className="el-control el-control--row">
                      <span className="el-control__label">Width</span>
                      <input
                        type="text"
                        className="el-input"
                        style={{ width: 60 }}
                        value={getValueForBreakpoint(style.borderWidth, breakpoint)}
                        onChange={(e) => updateResponsiveStyle(section.id, 'borderWidth', breakpoint, e.target.value)}
                        placeholder="1px"
                      />
                    </div>
                    <ColorInput label="Color" value={getStyle('borderColor')} onChange={(v) => handleChange({ borderColor: v })} />
                  </>
                )}

                {/* Border Radius */}
                <div className="el-control">
                  <div className="el-control__header">
                    <span className="el-control__label">Border Radius</span>
                    <div className="el-control__right">
                      <span className="el-unit-btn">px</span>
                    </div>
                  </div>
                  <div className="el-four-input">
                    {radiusLinked ? (
                      <>
                        <input className="el-input" value={borderRadiusVal} onChange={(e) => updateResponsiveStyle(section.id, 'borderRadius', breakpoint, e.target.value)} placeholder="" />
                        <input className="el-input" value={borderRadiusVal} readOnly />
                        <input className="el-input" value={borderRadiusVal} readOnly />
                        <input className="el-input" value={borderRadiusVal} readOnly />
                      </>
                    ) : (
                      <>
                        <input className="el-input" value={borderRadiusVal} onChange={(e) => updateResponsiveStyle(section.id, 'borderRadius', breakpoint, e.target.value)} placeholder="" />
                        <input className="el-input" placeholder="" />
                        <input className="el-input" placeholder="" />
                        <input className="el-input" placeholder="" />
                      </>
                    )}
                    <button type="button" className={`el-link-btn ${radiusLinked ? 'el-link-btn--active' : ''}`} onClick={() => setRadiusLinked(!radiusLinked)}>
                      {radiusLinked ? <Link2 size={11} /> : <Unlink2 size={11} />}
                    </button>
                  </div>
                  <div className="el-four-input__labels">
                    <span>Top</span><span>Right</span><span>Bottom</span><span>Left</span>
                  </div>
                </div>

                {/* Box Shadow */}
                <div className="el-control el-control--row">
                  <span className="el-control__label">Box Shadow</span>
                  <input
                    type="text"
                    className="el-input el-input--full"
                    value={getStyle('boxShadow')}
                    onChange={(e) => handleChange({ boxShadow: e.target.value })}
                    placeholder="none"
                  />
                </div>
              </>
            ) : (
              <ColorInput label="Border Color" value={getStyle('hoverBorderColor')} onChange={(v) => handleChange({ hoverBorderColor: v })} />
            )}
          </div>
        )}
      </div>
    </div>
  );
}
