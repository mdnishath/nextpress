import { useState } from '@wordpress/element';
import { ChevronDown } from 'lucide-react';

interface AccordionSectionProps {
  title: string;
  defaultOpen?: boolean;
  children: React.ReactNode;
}

export function AccordionSection({ title, defaultOpen = false, children }: AccordionSectionProps) {
  const [open, setOpen] = useState(defaultOpen);

  return (
    <div className="npb-accordion">
      <button
        className="npb-accordion__header"
        onClick={() => setOpen(!open)}
        type="button"
      >
        <span className="npb-accordion__title">{title}</span>
        <ChevronDown
          size={14}
          className="npb-accordion__chevron"
          style={{
            transform: open ? 'rotate(0deg)' : 'rotate(-90deg)',
            transition: 'transform 0.2s',
          }}
        />
      </button>
      {open && <div className="npb-accordion__body">{children}</div>}
    </div>
  );
}
