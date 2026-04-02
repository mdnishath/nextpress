import { LayoutGrid, Contrast, Settings } from 'lucide-react';
import { useBuilderStore } from '../../store/builderStore';
import { useUIStore } from '../../store/uiStore';
import { ContentEditor } from './ContentEditor';
import { ContainerContentEditor } from './ContainerContentEditor';
import { StyleEditor } from './StyleEditor';
import { AdvancedEditor } from './AdvancedEditor';
import { ErrorBoundary } from '../../components/ErrorBoundary';

type RightTab = 'content' | 'style' | 'advanced';

const TABS: { key: RightTab; label: string; Icon: typeof LayoutGrid }[] = [
  { key: 'content', label: 'Layout', Icon: LayoutGrid },
  { key: 'style', label: 'Style', Icon: Contrast },
  { key: 'advanced', label: 'Advanced', Icon: Settings },
];

export function RightPanel() {
  const { rightPanelOpen, rightTab, setRightTab } = useUIStore();
  const { selectedSectionId, getSection } = useBuilderStore();

  if (!rightPanelOpen) {
    return <div className="npb-right-panel npb-right-panel--collapsed" />;
  }

  const section = selectedSectionId ? getSection(selectedSectionId) : undefined;

  if (!section) {
    return (
      <div className="npb-right-panel npb-right-panel--dark">
        <div className="npb-no-selection">
          <div className="npb-no-selection__icon">
            <LayoutGrid size={36} />
          </div>
          <p style={{ fontSize: 14, fontWeight: 500, margin: '0 0 4px' }}>No element selected</p>
          <p style={{ fontSize: 13, margin: 0 }}>Click an element on the canvas to edit</p>
        </div>
      </div>
    );
  }

  const sectionLabel = section.section_type
    .replace(/-/g, ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());

  const isContainer = section.section_type === 'container';

  return (
    <div className="npb-right-panel npb-right-panel--dark">
      {/* Header */}
      <div className="npb-edit-header">
        <span className="npb-edit-header__title">Edit {sectionLabel}</span>
      </div>

      {/* Tabs — icon + label like Elementor */}
      <div className="npb-edit-tabs">
        {TABS.map((tab) => {
          const isActive = rightTab === tab.key;
          return (
            <button
              key={tab.key}
              className={`npb-edit-tabs__tab ${isActive ? 'npb-edit-tabs__tab--active' : ''}`}
              onClick={() => setRightTab(tab.key)}
              type="button"
            >
              <tab.Icon size={16} />
              <span>{tab.label}</span>
            </button>
          );
        })}
      </div>

      {/* Tab Content */}
      <div className="npb-edit-content">
        <ErrorBoundary name={`RightPanel:${rightTab}`}>
          {rightTab === 'content' && (
            isContainer
              ? <ContainerContentEditor section={section} />
              : <ContentEditor section={section} />
          )}
          {rightTab === 'style' && <StyleEditor section={section} />}
          {rightTab === 'advanced' && <AdvancedEditor section={section} />}
        </ErrorBoundary>
      </div>
    </div>
  );
}
