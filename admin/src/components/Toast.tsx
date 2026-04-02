import { useUIStore } from '../store/uiStore';
import { X } from 'lucide-react';

export function Toast() {
  const { toasts, removeToast } = useUIStore();

  if (toasts.length === 0) return null;

  return (
    <div className="npb-toasts">
      {toasts.map((toast) => (
        <div key={toast.id} className={`npb-toast npb-toast--${toast.type}`}>
          <span>{toast.message}</span>
          <button
            onClick={() => removeToast(toast.id)}
            style={{
              background: 'none',
              border: 'none',
              color: 'inherit',
              cursor: 'pointer',
              marginLeft: 12,
              padding: 0,
              display: 'inline-flex',
            }}
          >
            <X size={14} />
          </button>
        </div>
      ))}
    </div>
  );
}
