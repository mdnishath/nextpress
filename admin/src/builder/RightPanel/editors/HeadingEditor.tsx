import { useState, useEffect, useRef } from '@wordpress/element';
import {
  AlignLeft, AlignCenter, AlignRight, AlignJustify, Pencil,
  Italic, Underline, Strikethrough, CaseSensitive,
} from 'lucide-react';
import { useBuilderStore } from '../../../store/builderStore';
import { getValueForBreakpoint } from '../../../utils/responsive';
import { loadGoogleFont } from '../../../utils/fontLoader';
import { StepperInput } from '../controls/StepperInput';
import type { Section } from '../../../types/builder';

interface HeadingEditorProps {
  section: Section;
  tab: 'content' | 'style' | 'advanced';
}

const HTML_TAGS = [
  { label: 'H1', value: 'h1' },
  { label: 'H2', value: 'h2' },
  { label: 'H3', value: 'h3' },
  { label: 'H4', value: 'h4' },
  { label: 'H5', value: 'h5' },
  { label: 'H6', value: 'h6' },
];

export function HeadingContentEditor({ section }: { section: Section }) {
  const { updateContent } = useBuilderStore();
  const c = section.content as Record<string, string>;

  const update = (key: string, val: string) => {
    updateContent(section.id, { [key]: val });
  };

  return (
    <div>
      {/* ▼ Heading section */}
      <div className="el-section">
        <div className="el-section__header">
          <span className="el-section__arrow">▼</span>
          <span className="el-section__title">Heading</span>
        </div>
        <div className="el-section__body">
          {/* Title */}
          <div className="el-control">
            <span className="el-control__label">Title</span>
            <textarea
              className="el-textarea"
              rows={3}
              value={c.text || 'Add Your Heading Text Here'}
              onChange={(e) => update('text', e.target.value)}
              placeholder="Add Your Heading Text Here"
            />
          </div>

          {/* Link */}
          <div className="el-control">
            <span className="el-control__label">Link</span>
            <input
              className="el-input el-input--full"
              type="url"
              value={c.link || ''}
              onChange={(e) => update('link', e.target.value)}
              placeholder="Type or paste your URL"
            />
          </div>

          {/* HTML Tag — inline row */}
          <div className="el-control el-control--row">
            <span className="el-control__label">HTML Tag</span>
            <select
              className="el-select"
              value={c.tag || 'h2'}
              onChange={(e) => update('tag', e.target.value)}
            >
              {HTML_TAGS.map((t) => (
                <option key={t.value} value={t.value}>{t.label}</option>
              ))}
            </select>
          </div>
        </div>
      </div>
    </div>
  );
}

export function HeadingStyleEditor({ section }: { section: Section }) {
  const { updateStyle, updateResponsiveStyle, breakpoint } = useBuilderStore();
  const style = section.style;
  const [hoverTab, setHoverTab] = useState<'normal' | 'hover'>('normal');

  return (
    <div>
      {/* ▼ Heading section */}
      <div className="el-section">
        <div className="el-section__header">
          <span className="el-section__arrow">▼</span>
          <span className="el-section__title">Heading</span>
        </div>
        <div className="el-section__body">
          {/* Alignment — per-device responsive */}
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

          {/* Typography — label + edit icon */}
          <div className="el-control el-control--row">
            <span className="el-control__label">Typography</span>
            <TypographyPopup section={section} />
          </div>

          {/* Normal / Hover tabs */}
          <div className="el-hover-tabs" style={{ marginTop: 12 }}>
            <button
              type="button"
              className={`el-hover-tabs__btn ${hoverTab === 'normal' ? 'el-hover-tabs__btn--active' : ''}`}
              onClick={() => setHoverTab('normal')}
            >Normal</button>
            <button
              type="button"
              className={`el-hover-tabs__btn ${hoverTab === 'hover' ? 'el-hover-tabs__btn--active' : ''}`}
              onClick={() => setHoverTab('hover')}
            >Hover</button>
          </div>

          {/* Text Color — per-device */}
          <div className="el-control el-control--row" style={{ marginTop: 12 }}>
            <span className="el-control__label">Text Color</span>
            <div className="el-color-input">
              <div
                className="el-color-input__swatch"
                style={{ background: hoverTab === 'normal' ? getValueForBreakpoint(style.textColor, breakpoint) || '' : getValueForBreakpoint(style.hoverColor, breakpoint) }}
              >
                <input
                  type="color"
                  value={(hoverTab === 'normal' ? getValueForBreakpoint(style.textColor, breakpoint) : (style.hoverColor as string)) || '#000000'}
                  onChange={(e) => {
                    if (hoverTab === 'normal') updateResponsiveStyle(section.id, 'textColor', breakpoint, e.target.value);
                    else updateResponsiveStyle(section.id, 'hoverColor', breakpoint, e.target.value);
                  }}
                  className="el-color-input__native"
                />
              </div>
            </div>
          </div>

          {/* Text Shadow */}
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

          {/* Blend Mode */}
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
        </div>
      </div>
    </div>
  );
}

// ─── Typography Popup ───

function TypographyPopup({ section }: { section: Section }) {
  const { updateStyle, updateResponsiveStyle, breakpoint } = useBuilderStore();
  const style = section.style;
  const [open, setOpen] = useState(false);
  const popupRef = useRef<HTMLDivElement>(null);

  // Close on outside click
  useEffect(() => {
    if (!open) return;
    const handler = (e: MouseEvent) => {
      if (popupRef.current && !popupRef.current.contains(e.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [open]);

  const fontSize = getValueForBreakpoint(style.fontSize, breakpoint);
  const lineHeight = getValueForBreakpoint(style.lineHeight, breakpoint);
  const letterSpacing = getValueForBreakpoint(style.letterSpacing, breakpoint);

  /** Auto-append px if user types just a number */
  const withUnit = (val: string, defaultUnit = 'px') => {
    if (!val) return '';
    // If it's just a number, append default unit
    if (/^\d+(\.\d+)?$/.test(val.trim())) return `${val.trim()}${defaultUnit}`;
    return val;
  };

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
              {['Inter', 'Roboto', 'Open Sans', 'Poppins', 'Montserrat', 'Lato', 'Playfair Display', 'Merriweather', 'DM Sans', 'Space Grotesk'].map((f) => (
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

          {/* Weight — per-device */}
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

          {/* Font Style + Decoration — per-device */}
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

          {/* Text Transform — per-device */}
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
