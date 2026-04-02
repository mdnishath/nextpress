import { useMemo } from '@wordpress/element';
import { UnitSwitcher, parseValueWithUnit, formatValueWithUnit } from './UnitSwitcher';

interface SliderInputProps {
  value: string;
  onChange: (value: string) => void;
  min?: number;
  max?: number;
  step?: number;
  units?: string[];
  placeholder?: string;
}

/** Sensible max values per unit */
const UNIT_RANGES: Record<string, { min: number; max: number; step: number }> = {
  px: { min: 0, max: 1600, step: 1 },
  '%': { min: 0, max: 100, step: 1 },
  em: { min: 0, max: 20, step: 0.1 },
  rem: { min: 0, max: 20, step: 0.1 },
  vh: { min: 0, max: 100, step: 1 },
  vw: { min: 0, max: 100, step: 1 },
};

export function SliderInput({
  value,
  onChange,
  min,
  max,
  step,
  units = ['px', '%'],
  placeholder = '0',
}: SliderInputProps) {
  const parsed = useMemo(() => parseValueWithUnit(value, units[0]), [value, units]);
  const range = UNIT_RANGES[parsed.unit] || UNIT_RANGES.px;

  const effectiveMin = min ?? range.min;
  const effectiveMax = max ?? range.max;
  const effectiveStep = step ?? range.step;

  const handleNumberChange = (num: number) => {
    onChange(formatValueWithUnit(num, parsed.unit));
  };

  const handleUnitChange = (newUnit: string) => {
    // Convert value when switching units (rough mapping)
    const newRange = UNIT_RANGES[newUnit] || UNIT_RANGES.px;
    const ratio = parsed.number / (effectiveMax || 1);
    const converted = Math.round(ratio * newRange.max);
    onChange(formatValueWithUnit(converted, newUnit));
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const raw = e.target.value;
    if (raw === '' || raw === '-') {
      onChange('');
      return;
    }
    const num = parseFloat(raw);
    if (!isNaN(num)) {
      handleNumberChange(num);
    }
  };

  return (
    <div className="npb-slider-input">
      <input
        type="range"
        className="npb-slider-input__slider"
        min={effectiveMin}
        max={effectiveMax}
        step={effectiveStep}
        value={parsed.number || 0}
        onChange={(e) => handleNumberChange(parseFloat(e.target.value))}
      />
      <input
        type="number"
        className="npb-slider-input__number"
        value={parsed.number || ''}
        onChange={handleInputChange}
        placeholder={placeholder}
        min={effectiveMin}
        max={effectiveMax}
        step={effectiveStep}
      />
      {units.length > 1 && (
        <UnitSwitcher
          units={units}
          activeUnit={parsed.unit}
          onChange={handleUnitChange}
        />
      )}
    </div>
  );
}
