import { useUIStore } from '../store/uiStore';

export function Confirm() {
  const { confirmDialog, hideConfirm } = useUIStore();

  if (!confirmDialog.open) return null;

  const handleConfirm = () => {
    confirmDialog.onConfirm?.();
    hideConfirm();
  };

  return (
    <div className="npb-confirm-overlay" onClick={hideConfirm}>
      <div className="npb-confirm-dialog" onClick={(e) => e.stopPropagation()}>
        <h4 className="npb-confirm-dialog__title">{confirmDialog.title}</h4>
        <p className="npb-confirm-dialog__message">{confirmDialog.message}</p>
        <div className="npb-confirm-dialog__actions">
          <button className="npb-btn npb-btn--secondary" onClick={hideConfirm}>
            Cancel
          </button>
          <button className="npb-btn npb-btn--danger" onClick={handleConfirm}>
            Delete
          </button>
        </div>
      </div>
    </div>
  );
}
