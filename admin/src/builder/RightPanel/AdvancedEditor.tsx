import { useState } from '@wordpress/element';
import { Monitor, Tablet, Smartphone, Link2, Unlink2 } from 'lucide-react';
import { useBuilderStore } from '../../store/builderStore';
import { getValueForBreakpoint } from '../../utils/responsive';
import { StepperInput } from './controls/StepperInput';
import type { Section } from '../../types/builder';

interface AdvancedEditorProps {
  section: Section;
}

// ─── Inline 4-input spacing (Elementor-style) ───

function SpacingRow({ label, sectionId, prefix }: {
  label: string;
  sectionId: string;
  prefix: 'padding' | 'margin';
}) {
  const { breakpoint, updateResponsiveStyle } = useBuilderStore();
  const style = useBuilderStore((s) => s.sections.find((sec) => sec.id === sectionId)?.style || {});
  const [linked, setLinked] = useState(true);
  const [unit, setUnit] = useState('px');

  const sides = ['Top', 'Right', 'Bottom', 'Left'] as const;
  const values = sides.map((s) => getValueForBreakpoint(style[`${prefix}${s}`], breakpoint));

  const handleChange = (idx: number, val: string) => {
    if (linked) {
      sides.forEach((s) => updateResponsiveStyle(sectionId, `${prefix}${s}`, breakpoint, val));
    } else {
      updateResponsiveStyle(sectionId, `${prefix}${sides[idx]}`, breakpoint, val);
    }
  };

  return (
    <div className="el-control">
      <div className="el-control__header">
        <div className="el-control__left">
          <span className="el-control__label">{label}</span>
          <button type="button" className="el-responsive-icon" onClick={() => {
            const bp = useBuilderStore.getState().breakpoint;
            useBuilderStore.getState().setBreakpoint(bp === 'desktop' ? 'tablet' : bp === 'tablet' ? 'mobile' : 'desktop');
          }}>🖥</button>
        </div>
        <button type="button" className="el-unit-btn" onClick={() => setUnit(unit === 'px' ? '%' : unit === '%' ? 'em' : 'px')}>
          {unit} ▾
        </button>
      </div>
      <div className="el-four-input">
        {sides.map((side, i) => (
          <input
            key={side}
            type="number"
            className="el-input"
            value={values[i]?.replace(/[a-z%]+/i, '') || '0'}
            onChange={(e) => handleChange(i, `${e.target.value || '0'}${unit}`)}
            placeholder="0"
          />
        ))}
        <button type="button" className={`el-link-btn ${linked ? 'el-link-btn--active' : ''}`} onClick={() => setLinked(!linked)}>
          {linked ? <Link2 size={11} /> : <Unlink2 size={11} />}
        </button>
      </div>
      <div className="el-four-input__labels">
        <span>Top</span><span>Right</span><span>Bottom</span><span>Left</span>
      </div>
    </div>
  );
}

export function AdvancedEditor({ section }: AdvancedEditorProps) {
  const { sections, setSections, updateStyle, updateResponsiveStyle, updateVisibility, breakpoint } = useBuilderStore();
  const style = section.style;
  const visibility = section.responsiveVisibility || { desktop: true, tablet: true, mobile: true };

  /** Per-device style update */
  const setDeviceStyle = (key: string, val: string) => {
    updateResponsiveStyle(section.id, key, breakpoint, val);
  };

  /** Read style for current device */
  const getStyle = (key: string): string => {
    return getValueForBreakpoint(style[key], breakpoint);
  };

  const [layoutOpen, setLayoutOpen] = useState(true);
  const [motionOpen, setMotionOpen] = useState(false);
  const [transformOpen, setTransformOpen] = useState(false);
  const [responsiveOpen, setResponsiveOpen] = useState(false);
  const [customCssOpen, setCustomCssOpen] = useState(false);

  const handleFieldChange = (key: keyof Section, value: string) => {
    const updated = sections.map((s) => s.id === section.id ? { ...s, [key]: value } : s);
    setSections(updated);
  };

  const DEVICES = [
    { key: 'desktop' as const, Icon: Monitor, label: 'Desktop' },
    { key: 'tablet' as const, Icon: Tablet, label: 'Tablet' },
    { key: 'mobile' as const, Icon: Smartphone, label: 'Mobile' },
  ];

  return (
    <div>
      {/* ▼ Layout */}
      <div className="el-section">
        <button type="button" className="el-section__header el-section__header--collapsible" onClick={() => setLayoutOpen(!layoutOpen)}>
          <span className="el-section__arrow">{layoutOpen ? '▼' : '▶'}</span>
          <span className="el-section__title">Layout</span>
        </button>

        {layoutOpen && (
          <div className="el-section__body">
            {/* Margin */}
            <SpacingRow label="Margin" sectionId={section.id} prefix="margin" />

            {/* Padding */}
            <SpacingRow label="Padding" sectionId={section.id} prefix="padding" />

            {/* Align Self */}
            <div className="el-control el-control--row">
              <div className="el-control__left">
                <span className="el-control__label">Align Self</span>
                <button type="button" className="el-responsive-icon">🖥</button>
              </div>
              <div className="el-icon-group">
                {[
                  { val: 'flex-start', tip: 'Start', label: '⊤' },
                  { val: 'center', tip: 'Center', label: '⊕' },
                  { val: 'flex-end', tip: 'End', label: '⊥' },
                  { val: 'stretch', tip: 'Stretch', label: '⊞' },
                ].map((o) => (
                  <button
                    key={o.val}
                    type="button"
                    className={`el-icon-group__btn ${getStyle('alignSelf') === o.val ? 'el-icon-group__btn--active' : ''}`}
                    onClick={() => setDeviceStyle('alignSelf', o.val)}
                    title={o.tip}
                  >
                    {o.label}
                  </button>
                ))}
              </div>
            </div>
            <p className="el-hint">This control will affect contained elements only.</p>

            {/* Order */}
            <div className="el-control el-control--row">
              <div className="el-control__left">
                <span className="el-control__label">Order</span>
                <button type="button" className="el-responsive-icon">🖥</button>
              </div>
              <div className="el-icon-group">
                {[
                  { val: '-1', tip: 'First', label: '⊢' },
                  { val: '99', tip: 'Last', label: '⊣' },
                  { val: 'custom', tip: 'Custom', label: '⋮' },
                ].map((o) => (
                  <button
                    key={o.val}
                    type="button"
                    className={`el-icon-group__btn ${getStyle('order') === o.val ? 'el-icon-group__btn--active' : ''}`}
                    onClick={() => setDeviceStyle('order', o.val)}
                    title={o.tip}
                  >
                    {o.label}
                  </button>
                ))}
              </div>
            </div>
            <p className="el-hint">This control will affect contained elements only.</p>

            {/* Size */}
            <div className="el-control el-control--row">
              <div className="el-control__left">
                <span className="el-control__label">Size</span>
                <button type="button" className="el-responsive-icon">🖥</button>
              </div>
              <div className="el-icon-group">
                {[
                  { val: 'none', tip: 'None', label: '⊘' },
                  { val: 'grow', tip: 'Grow', label: '⟷' },
                  { val: 'shrink', tip: 'Shrink', label: '⟵⟶' },
                  { val: 'custom', tip: 'Custom', label: '⋮' },
                ].map((o) => (
                  <button
                    key={o.val}
                    type="button"
                    className={`el-icon-group__btn ${getStyle('flexSize') === o.val ? 'el-icon-group__btn--active' : ''}`}
                    onClick={() => setDeviceStyle('flexSize', o.val)}
                    title={o.tip}
                  >
                    {o.label}
                  </button>
                ))}
              </div>
            </div>

            {getStyle('flexSize') === 'custom' && (
              <div style={{ display: 'flex', gap: 6, marginTop: 6 }}>
                {[
                  { key: 'flexGrow', label: 'Grow', def: '0' },
                  { key: 'flexShrink', label: 'Shrink', def: '1' },
                  { key: 'flexBasis', label: 'Basis', def: 'auto' },
                ].map((f) => (
                  <div key={f.key} style={{ flex: 1 }}>
                    <span className="el-four-input__labels"><span>{f.label}</span></span>
                    <input
                      className="el-input el-input--full"
                      value={(style[f.key] as string) || f.def}
                      onChange={(e) => setDeviceStyle(f.key, e.target.value)}
                    />
                  </div>
                ))}
              </div>
            )}

            {/* Position */}
            <div className="el-control el-control--row" style={{ marginTop: 12 }}>
              <span className="el-control__label">Position</span>
              <select className="el-select" value={getStyle('position')} onChange={(e) => setDeviceStyle('position', e.target.value)}>
                <option value="">Default</option>
                <option value="relative">Relative</option>
                <option value="absolute">Absolute</option>
                <option value="fixed">Fixed</option>
                <option value="sticky">Sticky</option>
              </select>
            </div>

            {/* Z-Index */}
            <div className="el-control el-control--row">
              <div className="el-control__left">
                <span className="el-control__label">Z-Index</span>
                <button type="button" className="el-responsive-icon">🖥</button>
              </div>
              <StepperInput value={getStyle('zIndex')} onChange={(v) => setDeviceStyle('zIndex', v)} placeholder="0" unit="" step={1} />
            </div>

            {/* CSS ID */}
            <div className="el-control el-control--row">
              <span className="el-control__label">CSS ID</span>
              <input className="el-input el-input--full" value={section.custom_id || ''} onChange={(e) => handleFieldChange('custom_id', e.target.value)} placeholder="" />
            </div>

            {/* CSS Classes */}
            <div className="el-control el-control--row">
              <span className="el-control__label">CSS Classes</span>
              <input className="el-input el-input--full" value={(style.cssClasses as string) || ''} onChange={(e) => updateStyle(section.id, { cssClasses: e.target.value })} placeholder="" />
              {/* CSS Classes not per-device — same on all breakpoints */}
            </div>
          </div>
        )}
      </div>

      {/* ▶ Motion Effects */}
      <div className="el-section">
        <button type="button" className="el-section__header el-section__header--collapsible" onClick={() => setMotionOpen(!motionOpen)}>
          <span className="el-section__arrow">{motionOpen ? '▼' : '▶'}</span>
          <span className="el-section__title">Motion Effects</span>
        </button>
        {motionOpen && <div className="el-section__body"><p className="el-hint">Coming soon in v2.</p></div>}
      </div>

      {/* ▶ Transform */}
      <div className="el-section">
        <button type="button" className="el-section__header el-section__header--collapsible" onClick={() => setTransformOpen(!transformOpen)}>
          <span className="el-section__arrow">{transformOpen ? '▼' : '▶'}</span>
          <span className="el-section__title">Transform</span>
        </button>
        {transformOpen && <div className="el-section__body"><p className="el-hint">Coming soon in v2.</p></div>}
      </div>

      {/* ▶ Responsive */}
      <div className="el-section">
        <button type="button" className="el-section__header el-section__header--collapsible" onClick={() => setResponsiveOpen(!responsiveOpen)}>
          <span className="el-section__arrow">{responsiveOpen ? '▼' : '▶'}</span>
          <span className="el-section__title">Responsive</span>
        </button>
        {responsiveOpen && (
          <div className="el-section__body">
            <div style={{ display: 'flex', gap: 6 }}>
              {DEVICES.map(({ key, Icon, label }) => {
                const isVisible = visibility[key];
                return (
                  <button
                    key={key}
                    type="button"
                    onClick={() => updateVisibility(section.id, { ...visibility, [key]: !isVisible })}
                    className={`el-device-btn ${isVisible ? 'el-device-btn--active' : ''}`}
                    title={`${isVisible ? 'Hide' : 'Show'} on ${label}`}
                  >
                    <Icon size={16} />
                    <span>{label}</span>
                  </button>
                );
              })}
            </div>
          </div>
        )}
      </div>

      {/* ▶ Custom CSS */}
      <div className="el-section">
        <button type="button" className="el-section__header el-section__header--collapsible" onClick={() => setCustomCssOpen(!customCssOpen)}>
          <span className="el-section__arrow">{customCssOpen ? '▼' : '▶'}</span>
          <span className="el-section__title">Custom CSS</span>
        </button>
        {customCssOpen && (
          <div className="el-section__body">
            <textarea
              className="el-textarea"
              rows={8}
              value={section.custom_css || ''}
              onChange={(e) => handleFieldChange('custom_css', e.target.value)}
              placeholder={`.np-section-${section.id} {\n  \n}`}
            />
          </div>
        )}
      </div>
    </div>
  );
}
