import { useCallback } from '@wordpress/element';
import { ChevronUp, ChevronDown } from 'lucide-react';

interface StepperInputProps {
  value: string;
  onChange: (value: string) => void;
  step?: number;
  min?: number;
  max?: number;
  placeholder?: string;
  unit?: string;
  style?: React.CSSProperties;
}

/** Parse numeric value from string like "32px" → 32 */
function extractNumber(val: string): number {
  const n = parseFloat(val);
  return isNaN(n) ? 0 : n;
}

/** Extract unit from string like "32px" → "px" */
function extractUnit(val: string, fallback = ''): string {
  const match = val.match(/[a-z%]+$/i);
  return match ? match[0] : fallback;
}

/**
 * Stepper Input — numeric input with increment/decrement buttons.
 * Elementor-style: input on left, up/down arrows on right.
 */
export function StepperInput({
  value,
  onChange,
  step = 1,
  min,
  max,
  placeholder = '',
  unit: forcedUnit,
  style: extraStyle,
}: StepperInputProps) {
  const numVal = extractNumber(value);
  const currentUnit = forcedUnit || extractUnit(value);

  const applyChange = useCallback((newNum: number) => {
    if (min !== undefined && newNum < min) newNum = min;
    if (max !== undefined && newNum > max) newNum = max;
    const u = currentUnit || (placeholder ? extractUnit(placeholder) : 'px');
    onChange(newNum === 0 && !currentUnit ? '' : `${newNum}${u}`);
  }, [onChange, currentUnit, min, max, placeholder]);

  const increment = useCallback(() => applyChange(numVal + step), [numVal, step, applyChange]);
  const decrement = useCallback(() => applyChange(numVal - step), [numVal, step, applyChange]);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const raw = e.target.value;
    if (raw === '' || raw === '-') {
      onChange('');
      return;
    }
    // If user types just a number, auto-append unit
    if (/^-?\d+(\.\d+)?$/.test(raw.trim())) {
      const u = currentUnit || forcedUnit || extractUnit(placeholder) || 'px';
      onChange(`${raw.trim()}${u}`);
    } else {
      onChange(raw);
    }
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'ArrowUp') { e.preventDefault(); increment(); }
    if (e.key === 'ArrowDown') { e.preventDefault(); decrement(); }
  };

  return (
    <div className="el-stepper" style={extraStyle}>
      <input
        type="text"
        className="el-stepper__input"
        value={value ? String(numVal) : ''}
        onChange={handleInputChange}
        onKeyDown={handleKeyDown}
        placeholder={placeholder}
      />
      <div className="el-stepper__btns">
        <button type="button" className="el-stepper__btn" onClick={increment} tabIndex={-1}>
          <ChevronUp size={10} />
        </button>
        <button type="button" className="el-stepper__btn" onClick={decrement} tabIndex={-1}>
          <ChevronDown size={10} />
        </button>
      </div>
    </div>
  );
}
