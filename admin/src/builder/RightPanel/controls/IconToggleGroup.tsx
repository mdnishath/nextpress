interface IconToggleOption {
  value: string;
  icon: React.ReactNode;
  tooltip: string;
}

interface IconToggleGroupProps {
  options: IconToggleOption[];
  value: string;
  onChange: (value: string) => void;
}

export function IconToggleGroup({ options, value, onChange }: IconToggleGroupProps) {
  return (
    <div className="npb-icon-toggle-group">
      {options.map((opt) => {
        const isActive = value === opt.value;
        return (
          <button
            key={opt.value}
            type="button"
            className={`npb-icon-toggle-group__btn ${isActive ? 'npb-icon-toggle-group__btn--active' : ''}`}
            onClick={() => onChange(opt.value)}
            title={opt.tooltip}
          >
            {opt.icon}
          </button>
        );
      })}
    </div>
  );
}
