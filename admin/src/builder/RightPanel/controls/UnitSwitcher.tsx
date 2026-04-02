interface UnitSwitcherProps {
  units: string[];
  activeUnit: string;
  onChange: (unit: string) => void;
}

export function UnitSwitcher({ units, activeUnit, onChange }: UnitSwitcherProps) {
  const currentIdx = units.indexOf(activeUnit);

  const handleClick = () => {
    const nextIdx = (currentIdx + 1) % units.length;
    onChange(units[nextIdx]);
  };

  return (
    <button
      type="button"
      className="npb-unit-switcher"
      onClick={handleClick}
      title={`Unit: ${activeUnit} (click to switch)`}
    >
      {activeUnit}
    </button>
  );
}

/** Parse a CSS value like "120px" into { number: 120, unit: "px" } */
export function parseValueWithUnit(
  value: string,
  defaultUnit = 'px',
): { number: number; unit: string } {
  if (!value || value === 'auto' || value === 'none' || value === 'inherit') {
    return { number: 0, unit: defaultUnit };
  }
  const match = value.match(/^(-?\d*\.?\d+)\s*(px|%|em|rem|vh|vw|ch|ex|vmin|vmax)?$/);
  if (match) {
    return { number: parseFloat(match[1]), unit: match[2] || defaultUnit };
  }
  return { number: 0, unit: defaultUnit };
}

/** Format number + unit back to CSS value string */
export function formatValueWithUnit(num: number, unit: string): string {
  if (num === 0 && unit === 'px') return '0';
  return `${num}${unit}`;
}
