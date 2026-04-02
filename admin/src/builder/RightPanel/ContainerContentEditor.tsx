import { useState } from '@wordpress/element';
import {
  ArrowRight, ArrowLeft, ArrowDown, ArrowUp,
  Link2, Unlink2, ChevronDown,
} from 'lucide-react';
import { useBuilderStore } from '../../store/builderStore';
import { ResponsiveControl } from './controls/ResponsiveControl';
import { getValueForBreakpoint } from '../../utils/responsive';
import { parseValueWithUnit, formatValueWithUnit } from './controls/UnitSwitcher';
import { StepperInput } from './controls/StepperInput';
import type { Section, ContainerLayout, HtmlTag } from '../../types/builder';

interface ContainerContentEditorProps {
  section: Section;
}

const HTML_TAGS: HtmlTag[] = ['div', 'section', 'article', 'aside', 'header', 'footer', 'main', 'nav'];

// ─── Shared inline control components (Elementor-style) ───

function ControlRow({ label, responsive, value, right, children }: {
  label: string;
  responsive?: boolean;
  value?: unknown;
  right?: React.ReactNode;
  children: React.ReactNode;
}) {
  return (
    <div className="el-control">
      <div className="el-control__header">
        <span className="el-control__label">{label}</span>
        <div className="el-control__right">
          {responsive && <ResponsiveIcon />}
          {right}
        </div>
      </div>
      {children}
    </div>
  );
}

function ResponsiveIcon() {
  const { breakpoint, setBreakpoint } = useBuilderStore();
  return (
    <button
      type="button"
      className="el-responsive-icon"
      onClick={() => {
        const next = breakpoint === 'desktop' ? 'tablet' : breakpoint === 'tablet' ? 'mobile' : 'desktop';
        setBreakpoint(next);
      }}
      title={`Current: ${breakpoint}`}
    >
      {breakpoint === 'desktop' ? '🖥' : breakpoint === 'tablet' ? '📱' : '📱'}
    </button>
  );
}

function UnitBtn({ units, active, onChange }: { units: string[]; active: string; onChange: (u: string) => void }) {
  const idx = units.indexOf(active);
  return (
    <button
      type="button"
      className="el-unit-btn"
      onClick={() => onChange(units[(idx + 1) % units.length])}
    >
      {active} <ChevronDown size={8} />
    </button>
  );
}

function SliderWithInput({ value, onChange, min = 0, max = 1600, step = 1, units = ['px', '%'], placeholder = '' }: {
  value: string;
  onChange: (v: string) => void;
  min?: number;
  max?: number;
  step?: number;
  units?: string[];
  placeholder?: string;
}) {
  const parsed = parseValueWithUnit(value || '', units[0]);
  const UNIT_MAX: Record<string, number> = { px: 1600, '%': 100, vh: 100, vw: 100, em: 20, rem: 20 };

  return (
    <div className="el-slider-input">
      <input
        type="range"
        className="el-slider-input__range"
        min={min}
        max={UNIT_MAX[parsed.unit] || max}
        step={step}
        value={parsed.number || 0}
        onChange={(e) => onChange(formatValueWithUnit(parseFloat(e.target.value), parsed.unit))}
      />
      <StepperInput
        value={value}
        onChange={onChange}
        step={step}
        min={min}
        max={UNIT_MAX[parsed.unit] || max}
        placeholder={placeholder}
        unit={parsed.unit}
      />
    </div>
  );
}

function IconBtnGroup({ options, value, onChange }: {
  options: { val: string; icon: React.ReactNode; tip: string }[];
  value: string;
  onChange: (v: string) => void;
}) {
  return (
    <div className="el-icon-group">
      {options.map((o) => (
        <button
          key={o.val}
          type="button"
          className={`el-icon-group__btn ${value === o.val ? 'el-icon-group__btn--active' : ''}`}
          onClick={() => onChange(o.val)}
          title={o.tip}
        >
          {o.icon}
        </button>
      ))}
    </div>
  );
}

// ─── Justify/Align SVG icons ───

function JustifyIcon({ type }: { type: string }) {
  const lines: Record<string, number[]> = {
    'flex-start': [3, 7], 'center': [6, 10], 'flex-end': [9, 13],
    'space-between': [3, 13], 'space-around': [4, 8, 12], 'space-evenly': [3, 8, 13],
  };
  const pos = lines[type] || [3, 7];
  return (
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5">
      {pos.map((x, i) => <line key={i} x1={x} y1="4" x2={x} y2="12" strokeWidth="2" strokeLinecap="round" />)}
    </svg>
  );
}

function AlignIcon({ type }: { type: string }) {
  const configs: Record<string, { y1: number; y2: number; h: number }[]> = {
    'flex-start': [{ y1: 2, y2: 2, h: 8 }, { y1: 2, y2: 2, h: 6 }],
    'center': [{ y1: 4, y2: 4, h: 8 }, { y1: 5, y2: 5, h: 6 }],
    'flex-end': [{ y1: 6, y2: 6, h: 8 }, { y1: 8, y2: 8, h: 6 }],
    'stretch': [{ y1: 2, y2: 2, h: 12 }, { y1: 2, y2: 2, h: 12 }],
  };
  const bars = configs[type] || configs['flex-start'];
  return (
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
      {bars.map((b, i) => <line key={i} x1={5 + i * 6} y1={b.y1} x2={5 + i * 6} y2={b.y1 + b.h} />)}
    </svg>
  );
}

// ─── Main Component ───

export function ContainerContentEditor({ section }: ContainerContentEditorProps) {
  const { updateLayout, updateResponsiveLayout, updateResponsiveStyle, breakpoint } = useBuilderStore();
  const layout = section.layout || {};

  const setLayout = (key: keyof ContainerLayout, value: unknown) => {
    updateLayout(section.id, { [key]: value });
  };

  const isFlex = layout.type !== 'grid';

  // Gap state
  const [gapLinked, setGapLinked] = useState(true);
  const gapVal = getValueForBreakpoint(layout.gap, breakpoint);
  const colGap = getValueForBreakpoint(layout.columnGap, breakpoint);
  const rowGap = getValueForBreakpoint(layout.rowGap, breakpoint);

  // Width unit state
  const widthVal = getValueForBreakpoint(layout.maxWidth, breakpoint) || '';
  const widthParsed = parseValueWithUnit(widthVal, 'px');
  const [widthUnit, setWidthUnit] = useState(widthParsed.unit);

  const minHVal = getValueForBreakpoint(section.style.minHeight, breakpoint) || '';
  const minHParsed = parseValueWithUnit(minHVal, 'px');
  const [minHUnit, setMinHUnit] = useState(minHParsed.unit);

  // Gap unit
  const gapParsed = parseValueWithUnit(gapVal || '', 'px');
  const [gapUnit, setGapUnit] = useState(gapParsed.unit);

  // Additional options state
  const [showAdditional, setShowAdditional] = useState(false);

  return (
    <div>
      {/* ▼ Container section */}
      <div className="el-section">
        <button type="button" className="el-section__header" onClick={() => {}}>
          <span className="el-section__title">Container</span>
        </button>

        {/* Container Layout */}
        <div className="el-control el-control--row">
          <span className="el-control__label">Container Layout</span>
          <select
            className="el-select"
            value={layout.type || 'flex'}
            onChange={(e) => setLayout('type', e.target.value)}
          >
            <option value="flex">Flexbox</option>
            <option value="grid">Grid</option>
          </select>
        </div>

        {/* Content Width */}
        <div className="el-control el-control--row">
          <span className="el-control__label">Content Width</span>
          <select
            className="el-select"
            value={layout.contentWidth || 'full-width'}
            onChange={(e) => setLayout('contentWidth', e.target.value)}
          >
            <option value="boxed">Boxed</option>
            <option value="full-width">Full Width</option>
          </select>
        </div>

        {/* Width */}
        {layout.contentWidth === 'boxed' && (
          <div className="el-control">
            <div className="el-control__header">
              <span className="el-control__label">Width</span>
              <div className="el-control__right">
                <ResponsiveIcon />
                <UnitBtn units={['px', '%']} active={widthUnit} onChange={(u) => {
                  setWidthUnit(u);
                  updateResponsiveLayout(section.id, 'maxWidth', breakpoint, formatValueWithUnit(widthParsed.number, u));
                }} />
              </div>
            </div>
            <SliderWithInput
              value={widthVal}
              onChange={(v) => updateResponsiveLayout(section.id, 'maxWidth', breakpoint, v)}
              units={[widthUnit]}
            />
          </div>
        )}

        {/* Min Height */}
        <div className="el-control">
          <div className="el-control__header">
            <span className="el-control__label">Min Height</span>
            <div className="el-control__right">
              <ResponsiveIcon />
              <UnitBtn units={['px', 'vh']} active={minHUnit} onChange={(u) => {
                setMinHUnit(u);
                if (minHVal) {
                  updateResponsiveStyle(section.id, 'minHeight', breakpoint, formatValueWithUnit(minHParsed.number, u));
                }
              }} />
            </div>
          </div>
          <SliderWithInput
            value={minHVal}
            onChange={(v) => updateResponsiveStyle(section.id, 'minHeight', breakpoint, v)}
            units={[minHUnit]}
            placeholder=""
          />
          <p className="el-hint">To achieve full height Container use 100vh.</p>
        </div>
      </div>

      {/* Items section (no collapse — just a heading) */}
      <div className="el-section">
        <div className="el-section__header">
          <span className="el-section__title">Items</span>
        </div>

        {/* Direction */}
        {isFlex && (
          <div className="el-control el-control--row">
            <div className="el-control__left">
              <span className="el-control__label">Direction</span>
              <ResponsiveIcon />
            </div>
            <IconBtnGroup
              options={[
                { val: 'row', icon: <ArrowRight size={14} />, tip: 'Row' },
                { val: 'column', icon: <ArrowDown size={14} />, tip: 'Column' },
                { val: 'row-reverse', icon: <ArrowLeft size={14} />, tip: 'Row Reverse' },
                { val: 'column-reverse', icon: <ArrowUp size={14} />, tip: 'Column Reverse' },
              ]}
              value={(typeof layout.direction === 'string' ? layout.direction : '') || 'column'}
              onChange={(v) => setLayout('direction', v)}
            />
          </div>
        )}

        {/* Justify Content */}
        <div className="el-control">
          <div className="el-control__header">
            <span className="el-control__label">Justify Content</span>
            <ResponsiveIcon />
          </div>
          <IconBtnGroup
            options={[
              { val: 'flex-start', icon: <JustifyIcon type="flex-start" />, tip: 'Start' },
              { val: 'center', icon: <JustifyIcon type="center" />, tip: 'Center' },
              { val: 'flex-end', icon: <JustifyIcon type="flex-end" />, tip: 'End' },
              { val: 'space-between', icon: <JustifyIcon type="space-between" />, tip: 'Space Between' },
              { val: 'space-around', icon: <JustifyIcon type="space-around" />, tip: 'Space Around' },
              { val: 'space-evenly', icon: <JustifyIcon type="space-evenly" />, tip: 'Space Evenly' },
            ]}
            value={layout.justifyContent || ''}
            onChange={(v) => setLayout('justifyContent', v)}
          />
        </div>

        {/* Align Items */}
        <div className="el-control el-control--row">
          <div className="el-control__left">
            <span className="el-control__label">Align Items</span>
            <ResponsiveIcon />
          </div>
          <IconBtnGroup
            options={[
              { val: 'flex-start', icon: <AlignIcon type="flex-start" />, tip: 'Start' },
              { val: 'center', icon: <AlignIcon type="center" />, tip: 'Center' },
              { val: 'flex-end', icon: <AlignIcon type="flex-end" />, tip: 'End' },
              { val: 'stretch', icon: <AlignIcon type="stretch" />, tip: 'Stretch' },
            ]}
            value={layout.alignItems || ''}
            onChange={(v) => setLayout('alignItems', v)}
          />
        </div>

        {/* Gaps */}
        <div className="el-control">
          <div className="el-control__header">
            <span className="el-control__label">Gaps</span>
            <div className="el-control__right">
              <ResponsiveIcon />
              <UnitBtn units={['px', '%', 'em']} active={gapUnit} onChange={(u) => {
                setGapUnit(u);
                if (gapVal) {
                  updateResponsiveLayout(section.id, 'gap', breakpoint, formatValueWithUnit(gapParsed.number, u));
                }
              }} />
            </div>
          </div>
          <div className="el-gap-row">
            <div className="el-gap-row__input">
              <input
                type="number"
                className="el-input"
                value={gapLinked ? (gapParsed.number || '') : (parseValueWithUnit(colGap || '', gapUnit).number || '')}
                onChange={(e) => {
                  const v = formatValueWithUnit(parseFloat(e.target.value) || 0, gapUnit);
                  if (gapLinked) {
                    updateResponsiveLayout(section.id, 'gap', breakpoint, v);
                  } else {
                    updateResponsiveLayout(section.id, 'columnGap', breakpoint, v);
                  }
                }}
                placeholder="20"
              />
              <span className="el-gap-row__label">Column</span>
            </div>
            <div className="el-gap-row__input">
              <input
                type="number"
                className="el-input"
                value={gapLinked ? (gapParsed.number || '') : (parseValueWithUnit(rowGap || '', gapUnit).number || '')}
                onChange={(e) => {
                  const v = formatValueWithUnit(parseFloat(e.target.value) || 0, gapUnit);
                  if (gapLinked) {
                    updateResponsiveLayout(section.id, 'gap', breakpoint, v);
                  } else {
                    updateResponsiveLayout(section.id, 'rowGap', breakpoint, v);
                  }
                }}
                placeholder="20"
              />
              <span className="el-gap-row__label">Row</span>
            </div>
            <button
              type="button"
              className={`el-link-btn ${gapLinked ? 'el-link-btn--active' : ''}`}
              onClick={() => setGapLinked(!gapLinked)}
              title={gapLinked ? 'Unlink gaps' : 'Link gaps'}
            >
              {gapLinked ? <Link2 size={12} /> : <Unlink2 size={12} />}
            </button>
          </div>
        </div>

        {/* Wrap */}
        {isFlex && (
          <div className="el-control el-control--row">
            <div className="el-control__left">
              <span className="el-control__label">Wrap</span>
              <ResponsiveIcon />
            </div>
            <IconBtnGroup
              options={[
                { val: 'nowrap', icon: <span style={{ fontSize: 10 }}>▶▶</span>, tip: 'No Wrap' },
                { val: 'wrap', icon: <span style={{ fontSize: 10 }}>↵▶</span>, tip: 'Wrap' },
              ]}
              value={layout.wrap || 'nowrap'}
              onChange={(v) => setLayout('wrap', v)}
            />
          </div>
        )}

        {isFlex && (
          <p className="el-hint">Items within the container can stay in a single line (No wrap), or break into multiple lines (Wrap).</p>
        )}

        {/* Grid columns */}
        {!isFlex && (
          <>
            <div className="el-control">
              <div className="el-control__header">
                <span className="el-control__label">Columns</span>
              </div>
              <IconBtnGroup
                options={[1, 2, 3, 4, 5, 6].map((n) => ({
                  val: `repeat(${n}, 1fr)`,
                  icon: <span style={{ fontSize: 11, fontWeight: 700 }}>{n}</span>,
                  tip: `${n} Column${n > 1 ? 's' : ''}`,
                }))}
                value={layout.columns || 'repeat(1, 1fr)'}
                onChange={(v) => setLayout('columns', v)}
              />
              <input
                className="el-input el-input--full"
                type="text"
                value={layout.columns || ''}
                onChange={(e) => setLayout('columns', e.target.value)}
                placeholder="repeat(3, 1fr)"
                style={{ marginTop: 6 }}
              />
            </div>
            <div className="el-control">
              <div className="el-control__header">
                <span className="el-control__label">Rows</span>
              </div>
              <input
                className="el-input el-input--full"
                type="text"
                value={layout.rows || ''}
                onChange={(e) => setLayout('rows', e.target.value)}
                placeholder="auto"
              />
            </div>
          </>
        )}
      </div>

      {/* ▶ Additional Options */}
      <div className="el-section">
        <button
          type="button"
          className="el-section__header el-section__header--collapsible"
          onClick={() => setShowAdditional(!showAdditional)}
        >
          <span className="el-section__arrow">{showAdditional ? '▼' : '▶'}</span>
          <span className="el-section__title">Additional Options</span>
        </button>

        {showAdditional && (
          <div className="el-section__body">
            <div className="el-control el-control--row">
              <span className="el-control__label">Overflow</span>
              <select
                className="el-select"
                value={getValueForBreakpoint(section.style.overflow, breakpoint)}
                onChange={(e) => useBuilderStore.getState().updateResponsiveStyle(section.id, 'overflow', breakpoint, e.target.value)}
              >
                <option value="">Default</option>
                <option value="hidden">Hidden</option>
                <option value="auto">Auto</option>
                <option value="scroll">Scroll</option>
              </select>
            </div>
            <div className="el-control el-control--row">
              <span className="el-control__label">HTML Tag</span>
              <select
                className="el-select"
                value={layout.htmlTag || 'div'}
                onChange={(e) => setLayout('htmlTag', e.target.value as HtmlTag)}
              >
                {HTML_TAGS.map((tag) => (
                  <option key={tag} value={tag}>{`<${tag}>`}</option>
                ))}
              </select>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
