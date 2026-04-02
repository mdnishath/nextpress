import { Monitor, Tablet, Smartphone } from 'lucide-react';
import { useBuilderStore } from '../../../store/builderStore';
import type { ContentField, Breakpoint } from '../../../types/builder';

interface FieldProps {
  field: ContentField;
  value: unknown;
  onChange: (value: unknown) => void;
}

const BREAKPOINT_ICONS: Record<Breakpoint, typeof Monitor> = {
  desktop: Monitor,
  tablet: Tablet,
  mobile: Smartphone,
};

/**
 * Responsive field — stores per-breakpoint values.
 * Value structure: { desktop: "...", tablet: "...", mobile: "..." }
 */
export function ResponsiveField({ field, value, onChange }: FieldProps) {
  const { breakpoint } = useBuilderStore();
  const values = (typeof value === 'object' && value !== null ? value : {}) as Record<string, string>;

  const currentValue = values[breakpoint] ?? values.desktop ?? '';

  const handleChange = (val: string) => {
    onChange({ ...values, [breakpoint]: val });
  };

  return (
    <div className="npb-field">
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6 }}>
        <label className="npb-field__label" style={{ marginBottom: 0 }}>{field.label}</label>
        <div style={{ display: 'flex', gap: 2 }}>
          {(Object.keys(BREAKPOINT_ICONS) as Breakpoint[]).map((bp) => {
            const Icon = BREAKPOINT_ICONS[bp];
            const hasValue = !!values[bp];
            return (
              <div
                key={bp}
                title={`${bp}${hasValue ? `: ${values[bp]}` : ''}`}
                style={{
                  width: 20, height: 20,
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  borderRadius: 4,
                  background: bp === breakpoint ? '#ede9fe' : 'transparent',
                  color: hasValue ? '#7c3aed' : '#d1d5db',
                }}
              >
                <Icon size={12} />
              </div>
            );
          })}
        </div>
      </div>
      <input
        className="npb-field__input"
        type="text"
        value={currentValue}
        placeholder={field.placeholder}
        onChange={(e) => handleChange(e.target.value)}
      />
    </div>
  );
}
