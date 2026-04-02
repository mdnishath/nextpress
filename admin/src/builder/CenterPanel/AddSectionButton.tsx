import { Plus } from 'lucide-react';
import { useUIStore } from '../../store/uiStore';

export function AddSectionButton() {
  const { setLeftTab } = useUIStore();

  const handleClick = () => {
    // Switch to components tab so user can pick one
    setLeftTab('components');
  };

  return (
    <button className="npb-add-section" onClick={handleClick}>
      <Plus size={18} style={{ marginRight: 6 }} />
      Add Section
    </button>
  );
}
