/**
 * Shared Style Controls — DRY building blocks for Style/Advanced tabs.
 * Every component editor reuses these instead of duplicating code.
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import {
  AlignLeft, AlignCenter, AlignRight, AlignJustify, Pencil,
  Italic, Underline, Strikethrough,
} from 'lucide-react';
import { useBuilderStore } from '../../../store/builderStore';
import { getValueForBreakpoint } from '../../../utils/responsive';
import { loadGoogleFont } from '../../../utils/fontLoader';
import { StepperInput } from './StepperInput';
import type { Section } from '../../../types/builder';

// ─── Shared Font List ───
const GOOGLE_FONTS = [
  'Inter', 'Roboto', 'Open Sans', 'Poppins', 'Montserrat',
  'Lato', 'Playfair Display', 'Merriweather', 'DM Sans', 'Space Grotesk',
];

// ─── Section Header (collapsible) ───

export function SectionHeader({ title, open, onToggle }: {
  title: string; open: boolean; onToggle: () => void;
}) {
  return (
    <button type="button" className="el-section__header el-section__header--collapsible" onClick={onToggle}>
      <span className="el-section__arrow">{open ? '▼' : '▶'}</span>
      <span className="el-section__title">{title}</span>
    </button>
  );
}

// ─── Hover Tab Bar (Normal / Hover toggle) ───

export function HoverTabBar({ active, onChange }: {
  active: 'normal' | 'hover'; onChange: (t: 'normal' | 'hover') => void;
}) {
  return (
    <div className="el-hover-tabs" style={{ marginTop: 12 }}>
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

// ─── Alignment Control (Left/Center/Right/Justify) ───

export function AlignmentControl({ section }: { section: Section }) {
  const { updateResponsiveStyle, breakpoint } = useBuilderStore();
  const style = section.style;

  return (
    <div className="el-control el-control--row">
      <div className="el-control__left">
        <span className="el-control__label">Alignment</span>
      </div>
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
            className={`el-icon-group__btn ${getValueForBreakpoint(style.textAlign, breakpoint) === o.val ? 'el-icon-group__btn--active' : ''}`}
            onClick={() => updateResponsiveStyle(section.id, 'textAlign', breakpoint, o.val)}
            title={o.tip}
          >
            {o.icon}
          </button>
        ))}
      </div>
    </div>
  );
}

// ─── Text Color with Normal/Hover ───

export function TextColorControl({ section }: { section: Section }) {
  const { updateResponsiveStyle, breakpoint } = useBuilderStore();
  const style = section.style;
  const [hoverTab, setHoverTab] = useState<'normal' | 'hover'>('normal');

  return (
    <>
      <HoverTabBar active={hoverTab} onChange={setHoverTab} />
      <div className="el-control el-control--row" style={{ marginTop: 12 }}>
        <span className="el-control__label">Text Color</span>
        <div className="el-color-input">
          <div
            className="el-color-input__swatch"
            style={{
              background: hoverTab === 'normal'
                ? getValueForBreakpoint(style.textColor, breakpoint) || ''
                : getValueForBreakpoint(style.hoverColor, breakpoint) || '',
            }}
          >
            <input
              type="color"
              value={
                (hoverTab === 'normal'
                  ? getValueForBreakpoint(style.textColor, breakpoint)
                  : getValueForBreakpoint(style.hoverColor, breakpoint)) || '#000000'
              }
              onChange={(e) => {
                if (hoverTab === 'normal') updateResponsiveStyle(section.id, 'textColor', breakpoint, e.target.value);
                else updateResponsiveStyle(section.id, 'hoverColor', breakpoint, e.target.value);
              }}
              className="el-color-input__native"
            />
          </div>
        </div>
      </div>
    </>
  );
}

// ─── Text Shadow ───

export function TextShadowControl({ section }: { section: Section }) {
  const { updateResponsiveStyle, breakpoint } = useBuilderStore();
  const style = section.style;

  return (
    <div className="el-control el-control--row" style={{ marginTop: 8 }}>
      <span className="el-control__label">Text Shadow</span>
      <input
        className="el-input"
        style={{ width: 120 }}
        value={getValueForBreakpoint(style.textShadow, breakpoint)}
        onChange={(e) => updateResponsiveStyle(section.id, 'textShadow', breakpoint, e.target.value)}
        placeholder="none"
      />
    </div>
  );
}

// ─── Blend Mode ───

export function BlendModeControl({ section }: { section: Section }) {
  const { updateResponsiveStyle, breakpoint } = useBuilderStore();
  const style = section.style;

  return (
    <div className="el-control el-control--row">
      <span className="el-control__label">Blend Mode</span>
      <select
        className="el-select"
        value={getValueForBreakpoint(style.mixBlendMode, breakpoint) || 'normal'}
        onChange={(e) => updateResponsiveStyle(section.id, 'mixBlendMode', breakpoint, e.target.value)}
      >
        <option value="normal">Normal</option>
        <option value="multiply">Multiply</option>
        <option value="screen">Screen</option>
        <option value="overlay">Overlay</option>
      </select>
    </div>
  );
}

// ─── Typography Popup (shared between all text components) ───

export function TypographyPopup({ section }: { section: Section }) {
  const { updateResponsiveStyle, breakpoint } = useBuilderStore();
  const style = section.style;
  const [open, setOpen] = useState(false);
  const popupRef = useRef<HTMLDivElement>(null);

  // Close on outside click
  useEffect(() => {
    if (!open) return;
    const handler = (e: MouseEvent) => {
      if (popupRef.current && !popupRef.current.contains(e.target as Node)) setOpen(false);
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [open]);

  const fontSize = getValueForBreakpoint(style.fontSize, breakpoint);
  const lineHeight = getValueForBreakpoint(style.lineHeight, breakpoint);
  const letterSpacing = getValueForBreakpoint(style.letterSpacing, breakpoint);

  return (
    <div style={{ position: 'relative' }} ref={popupRef}>
      <button
        type="button"
        className="el-icon-group__btn"
        onClick={() => setOpen(!open)}
        title="Typography settings"
        style={{ border: '1px solid rgba(255,255,255,0.12)', borderRadius: 3 }}
      >
        <Pencil size={12} />
      </button>

      {open && (
        <div className="el-typo-popup">
          {/* Font Family */}
          <div className="el-control">
            <span className="el-control__label">Family</span>
            <select
              className="el-select"
              style={{ width: '100%' }}
              value={getValueForBreakpoint(style.fontFamily, breakpoint)}
              onChange={(e) => {
                const font = e.target.value;
                updateResponsiveStyle(section.id, 'fontFamily', breakpoint, font);
                if (font) loadGoogleFont(font);
              }}
            >
              <option value="">Default</option>
              {GOOGLE_FONTS.map((f) => (
                <option key={f} value={f}>{f}</option>
              ))}
            </select>
          </div>

          {/* Size */}
          <div className="el-control el-control--row">
            <span className="el-control__label">Size</span>
            <StepperInput
              value={fontSize}
              onChange={(v) => updateResponsiveStyle(section.id, 'fontSize', breakpoint, v)}
              placeholder="px"
              unit="px"
            />
          </div>

          {/* Weight */}
          <div className="el-control el-control--row">
            <span className="el-control__label">Weight</span>
            <select
              className="el-select"
              value={getValueForBreakpoint(style.fontWeight, breakpoint)}
              onChange={(e) => updateResponsiveStyle(section.id, 'fontWeight', breakpoint, e.target.value)}
            >
              <option value="">Default</option>
              <option value="300">Light (300)</option>
              <option value="400">Regular (400)</option>
              <option value="500">Medium (500)</option>
              <option value="600">Semi Bold (600)</option>
              <option value="700">Bold (700)</option>
              <option value="800">Extra Bold (800)</option>
              <option value="900">Black (900)</option>
            </select>
          </div>

          {/* Line Height */}
          <div className="el-control el-control--row">
            <span className="el-control__label">Line Height</span>
            <StepperInput
              value={lineHeight}
              onChange={(v) => updateResponsiveStyle(section.id, 'lineHeight', breakpoint, v)}
              placeholder="em"
              unit="em"
              step={0.1}
            />
          </div>

          {/* Letter Spacing */}
          <div className="el-control el-control--row">
            <span className="el-control__label">Letter Spacing</span>
            <StepperInput
              value={letterSpacing}
              onChange={(v) => updateResponsiveStyle(section.id, 'letterSpacing', breakpoint, v)}
              placeholder="px"
              unit="px"
              step={0.5}
            />
          </div>

          {/* Font Style & Decoration */}
          <div className="el-control">
            <span className="el-control__label">Style</span>
            <div className="el-icon-group" style={{ marginTop: 4 }}>
              <button
                type="button"
                className={`el-icon-group__btn ${getValueForBreakpoint(style.fontStyle, breakpoint) === 'italic' ? 'el-icon-group__btn--active' : ''}`}
                onClick={() => {
                  const cur = getValueForBreakpoint(style.fontStyle, breakpoint);
                  updateResponsiveStyle(section.id, 'fontStyle', breakpoint, cur === 'italic' ? 'normal' : 'italic');
                }}
                title="Italic"
              >
                <Italic size={13} />
              </button>
              <button
                type="button"
                className={`el-icon-group__btn ${getValueForBreakpoint(style.textDecoration, breakpoint) === 'underline' ? 'el-icon-group__btn--active' : ''}`}
                onClick={() => {
                  const cur = getValueForBreakpoint(style.textDecoration, breakpoint);
                  updateResponsiveStyle(section.id, 'textDecoration', breakpoint, cur === 'underline' ? 'none' : 'underline');
                }}
                title="Underline"
              >
                <Underline size={13} />
              </button>
              <button
                type="button"
                className={`el-icon-group__btn ${getValueForBreakpoint(style.textDecoration, breakpoint) === 'line-through' ? 'el-icon-group__btn--active' : ''}`}
                onClick={() => {
                  const cur = getValueForBreakpoint(style.textDecoration, breakpoint);
                  updateResponsiveStyle(section.id, 'textDecoration', breakpoint, cur === 'line-through' ? 'none' : 'line-through');
                }}
                title="Strikethrough"
              >
                <Strikethrough size={13} />
              </button>
            </div>
          </div>

          {/* Text Transform */}
          <div className="el-control">
            <span className="el-control__label">Transform</span>
            <div className="el-icon-group" style={{ marginTop: 4 }}>
              {[
                { val: 'none', label: 'Aa', tip: 'None' },
                { val: 'uppercase', label: 'AB', tip: 'Uppercase' },
                { val: 'lowercase', label: 'ab', tip: 'Lowercase' },
                { val: 'capitalize', label: 'Ab', tip: 'Capitalize' },
              ].map((o) => (
                <button
                  key={o.val}
                  type="button"
                  className={`el-icon-group__btn ${(getValueForBreakpoint(style.textTransform, breakpoint) || 'none') === o.val ? 'el-icon-group__btn--active' : ''}`}
                  onClick={() => updateResponsiveStyle(section.id, 'textTransform', breakpoint, o.val)}
                  title={o.tip}
                  style={{ fontSize: 11, fontWeight: 700 }}
                >
                  {o.label}
                </button>
              ))}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

// ─── Complete Typography Section (Alignment + Typography + Color + Shadow + Blend) ───
// Use this as a one-liner in any component's Style tab

export function TypographySection({ section, title }: { section: Section; title: string }) {
  const [open, setOpen] = useState(true);

  return (
    <div className="el-section">
      <SectionHeader title={title} open={open} onToggle={() => setOpen(!open)} />
      {open && (
        <div className="el-section__body">
          <AlignmentControl section={section} />
          <div className="el-control el-control--row">
            <span className="el-control__label">Typography</span>
            <TypographyPopup section={section} />
          </div>
          <TextColorControl section={section} />
          <TextShadowControl section={section} />
          <BlendModeControl section={section} />
        </div>
      )}
    </div>
  );
}
