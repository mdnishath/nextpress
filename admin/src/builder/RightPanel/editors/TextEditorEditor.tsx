import { useState, useEffect, useRef } from '@wordpress/element';
import {
  AlignLeft, AlignCenter, AlignRight, AlignJustify, Pencil,
  Italic, Underline, Strikethrough,
} from 'lucide-react';
import { useBuilderStore } from '../../../store/builderStore';
import { getValueForBreakpoint } from '../../../utils/responsive';
import { loadGoogleFont } from '../../../utils/fontLoader';
import { StepperInput } from '../controls/StepperInput';
import type { Section } from '../../../types/builder';

/* ─── Content Editor ─── */

export function TextEditorContentEditor({ section }: { section: Section }) {
  const { updateContent } = useBuilderStore();
  const c = section.content as Record<string, string>;
  const [showHtml, setShowHtml] = useState(false);
  const [htmlText, setHtmlText] = useState(c.content || '');
  const editorRef = useRef<HTMLDivElement>(null);
  const initializedRef = useRef(false);

  // Set initial content only once
  useEffect(() => {
    if (editorRef.current && !initializedRef.current) {
      editorRef.current.innerHTML = c.content || '<p>Add your text here. Click to edit.</p>';
      initializedRef.current = true;
    }
  }, []);

  // Sync HTML textarea when switching modes
  useEffect(() => {
    if (showHtml) {
      setHtmlText(c.content || '');
    } else if (editorRef.current) {
      editorRef.current.innerHTML = c.content || '<p>Add your text here. Click to edit.</p>';
    }
  }, [showHtml]);

  const update = (key: string, val: string) => {
    updateContent(section.id, { [key]: val });
  };

  const handleInput = () => {
    if (editorRef.current) {
      update('content', editorRef.current.innerHTML);
    }
  };

  return (
    <div>
      <div className="el-section">
        <div className="el-section__header">
          <span className="el-section__arrow">▼</span>
          <span className="el-section__title">Text Editor</span>
        </div>
        <div className="el-section__body">
          {/* Rich text / HTML toggle */}
          <div style={{ display: 'flex', justifyContent: 'flex-end', marginBottom: 6 }}>
            <button
              onClick={() => setShowHtml(!showHtml)}
              style={{ fontSize: 11, color: '#7c3aed', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 600 }}
            >
              {showHtml ? 'Visual' : 'HTML'}
            </button>
          </div>

          {showHtml ? (
            <textarea
              className="el-textarea"
              rows={8}
              value={htmlText}
              onChange={(e) => {
                setHtmlText(e.target.value);
                update('content', e.target.value);
              }}
              style={{ fontFamily: 'var(--npb-font-mono)', fontSize: 12 }}
            />
          ) : (
            <>
              {/* Formatting toolbar */}
              <div style={{
                display: 'flex', gap: 2, padding: '4px 8px',
                border: '1px solid rgba(255,255,255,0.12)', borderBottom: 'none',
                borderRadius: '4px 4px 0 0', background: 'rgba(255,255,255,0.05)',
              }}>
                {[
                  { cmd: 'bold', label: 'B', style: { fontWeight: 700 } as React.CSSProperties },
                  { cmd: 'italic', label: 'I', style: { fontStyle: 'italic' } as React.CSSProperties },
                  { cmd: 'underline', label: 'U', style: { textDecoration: 'underline' } as React.CSSProperties },
                  { cmd: 'strikeThrough', label: 'S', style: { textDecoration: 'line-through' } as React.CSSProperties },
                ].map((btn) => (
                  <button
                    key={btn.cmd}
                    onMouseDown={(e) => {
                      e.preventDefault();
                      document.execCommand(btn.cmd);
                      // Update store after formatting
                      setTimeout(() => {
                        if (editorRef.current) update('content', editorRef.current.innerHTML);
                      }, 0);
                    }}
                    style={{
                      width: 28, height: 28, border: 'none', background: 'transparent',
                      cursor: 'pointer', fontSize: 13, borderRadius: 4, color: '#e0e0e0', ...btn.style,
                    }}
                  >
                    {btn.label}
                  </button>
                ))}
              </div>
              <div
                ref={editorRef}
                contentEditable
                onInput={handleInput}
                style={{
                  minHeight: 120, padding: '10px 12px',
                  border: '1px solid rgba(255,255,255,0.12)', borderTop: 'none',
                  borderRadius: '0 0 4px 4px',
                  background: 'rgba(255,255,255,0.05)', color: '#e0e0e0',
                  lineHeight: 1.6, outline: 'none', fontSize: 13,
                  wordWrap: 'break-word', whiteSpace: 'pre-wrap',
                }}
                suppressContentEditableWarning
              />
            </>
          )}
        </div>
      </div>
    </div>
  );
}

/* ─── Style Editor (same as Heading) ─── */

export function TextEditorStyleEditor({ section }: { section: Section }) {
  const { updateResponsiveStyle, breakpoint } = useBuilderStore();
  const style = section.style;
  const [hoverTab, setHoverTab] = useState<'normal' | 'hover'>('normal');

  return (
    <div>
      <div className="el-section">
        <div className="el-section__header">
          <span className="el-section__arrow">▼</span>
          <span className="el-section__title">Text Editor</span>
        </div>
        <div className="el-section__body">
          {/* Alignment */}
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

          {/* Typography popup */}
          <div className="el-control el-control--row">
            <span className="el-control__label">Typography</span>
            <TypographyPopup section={section} />
          </div>

          {/* Normal / Hover */}
          <div className="el-hover-tabs" style={{ marginTop: 12 }}>
            <button type="button" className={`el-hover-tabs__btn ${hoverTab === 'normal' ? 'el-hover-tabs__btn--active' : ''}`} onClick={() => setHoverTab('normal')}>Normal</button>
            <button type="button" className={`el-hover-tabs__btn ${hoverTab === 'hover' ? 'el-hover-tabs__btn--active' : ''}`} onClick={() => setHoverTab('hover')}>Hover</button>
          </div>

          {/* Text Color */}
          <div className="el-control el-control--row" style={{ marginTop: 12 }}>
            <span className="el-control__label">Text Color</span>
            <div className="el-color-input">
              <div
                className="el-color-input__swatch"
                style={{ background: hoverTab === 'normal' ? getValueForBreakpoint(style.textColor, breakpoint) || '' : getValueForBreakpoint(style.hoverColor, breakpoint) }}
              >
                <input
                  type="color"
                  value={(hoverTab === 'normal' ? getValueForBreakpoint(style.textColor, breakpoint) : getValueForBreakpoint(style.hoverColor, breakpoint)) || '#000000'}
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

/* ─── Typography Popup (copied from HeadingEditor) ─── */

function TypographyPopup({ section }: { section: Section }) {
  const { updateResponsiveStyle, breakpoint } = useBuilderStore();
  const style = section.style;
  const [open, setOpen] = useState(false);
  const popupRef = useRef<HTMLDivElement>(null);

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

          {/* Font Style + Decoration */}
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
