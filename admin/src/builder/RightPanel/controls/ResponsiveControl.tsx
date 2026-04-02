/**
 * ResponsiveControl — wraps any input to add a breakpoint device toggle.
 * Shows Monitor/Tablet/Phone icons. Dot indicators for breakpoints with custom values.
 * Clicking a device switches the global breakpoint.
 */

import { Monitor, Tablet, Smartphone } from 'lucide-react';
import { useBuilderStore } from '../../../store/builderStore';
import type { Breakpoint, ResponsiveString } from '../../../types/builder';
import { hasBreakpointOverrides } from '../../../utils/responsive';

interface ResponsiveControlProps {
  label: string;
  value?: ResponsiveString;
  children: React.ReactNode;
}

const DEVICES: { bp: Breakpoint; Icon: typeof Monitor; tip: string }[] = [
  { bp: 'desktop', Icon: Monitor, tip: 'Desktop' },
  { bp: 'tablet', Icon: Tablet, tip: 'Tablet' },
  { bp: 'mobile', Icon: Smartphone, tip: 'Mobile' },
];

export function ResponsiveControl({ label, value, children }: ResponsiveControlProps) {
  const { breakpoint, setBreakpoint } = useBuilderStore();
  const overrides = hasBreakpointOverrides(value);

  return (
    <div className="npb-field">
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 6 }}>
        <label className="npb-field__label" style={{ margin: 0 }}>{label}</label>
        <div style={{ display: 'flex', gap: 2 }}>
          {DEVICES.map(({ bp, Icon, tip }) => {
            const isActive = breakpoint === bp;
            const hasOverride = bp === 'tablet' ? overrides.tablet : bp === 'desktop' ? overrides.desktop : false;
            return (
              <button
                key={bp}
                onClick={() => setBreakpoint(bp)}
                title={tip}
                style={{
                  position: 'relative',
                  width: 24, height: 24,
                  border: 'none', borderRadius: 4,
                  cursor: 'pointer',
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  background: isActive ? '#ede9fe' : 'transparent',
                  color: isActive ? '#7c3aed' : '#a1a1aa',
                  transition: 'all 0.15s',
                }}
              >
                <Icon size={13} />
                {hasOverride && (
                  <span style={{
                    position: 'absolute', top: 2, right: 2,
                    width: 5, height: 5, borderRadius: '50%',
                    background: '#7c3aed',
                  }} />
                )}
              </button>
            );
          })}
        </div>
      </div>
      {children}
    </div>
  );
}
