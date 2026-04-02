import { useState } from '@wordpress/element';

type HoverState = 'normal' | 'hover';

interface HoverTabsProps {
  children: (activeState: HoverState) => React.ReactNode;
}

export function HoverTabs({ children }: HoverTabsProps) {
  const [active, setActive] = useState<HoverState>('normal');

  return (
    <div>
      <div className="npb-hover-tabs">
        {(['normal', 'hover'] as const).map((state) => (
          <button
            key={state}
            type="button"
            className={`npb-hover-tabs__tab ${active === state ? 'npb-hover-tabs__tab--active' : ''}`}
            onClick={() => setActive(state)}
          >
            {state === 'normal' ? 'Normal' : 'Hover'}
          </button>
        ))}
      </div>
      {children(active)}
    </div>
  );
}
