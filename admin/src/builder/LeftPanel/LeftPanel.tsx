import type { ComponentType } from 'react';
import { ChevronLeft, ChevronRight, LayoutGrid, Pencil, Contrast, Settings, ArrowLeft } from 'lucide-react';
import { useBuilderStore } from '../../store/builderStore';
import { useUIStore } from '../../store/uiStore';
import { ComponentPalette } from './ComponentPalette';
import { ElementNavigator } from './ElementNavigator';
import { ContentEditor } from '../RightPanel/ContentEditor';
import { ContainerContentEditor } from '../RightPanel/ContainerContentEditor';
import { HeadingContentEditor, HeadingStyleEditor } from '../RightPanel/editors/HeadingEditor';
import { TextEditorContentEditor, TextEditorStyleEditor } from '../RightPanel/editors/TextEditorEditor';
import { StyleEditor } from '../RightPanel/StyleEditor';
import { AdvancedEditor } from '../RightPanel/AdvancedEditor';
import { ErrorBoundary } from '../../components/ErrorBoundary';
import type { Section } from '../../types/builder';

type EditTab = 'content' | 'style' | 'advanced';

/**
 * Component editor registry — maps section_type to specialized editors.
 * Add new component editors here. Fallback: ContentEditor (content), StyleEditor (style).
 */
const EDITOR_MAP: Record<string, {
  content?: ComponentType<{ section: Section }>;
  style?: ComponentType<{ section: Section }>;
}> = {
  container: { content: ContainerContentEditor },
  heading:   { content: HeadingContentEditor, style: HeadingStyleEditor },
  text_editor: { content: TextEditorContentEditor, style: TextEditorStyleEditor },
};

function getEditor(sectionType: string, tab: 'content' | 'style'): ComponentType<{ section: Section }> {
  return EDITOR_MAP[sectionType]?.[tab] || (tab === 'content' ? ContentEditor : StyleEditor);
}

function getEditTabs(isContainer: boolean): { key: EditTab; label: string; Icon: typeof LayoutGrid }[] {
  return [
    { key: 'content', label: isContainer ? 'Layout' : 'Content', Icon: isContainer ? LayoutGrid : Pencil },
    { key: 'style', label: 'Style', Icon: Contrast },
    { key: 'advanced', label: 'Advanced', Icon: Settings },
  ];
}

export function LeftPanel() {
  const { leftPanelOpen, leftTab, setLeftTab, rightTab, setRightTab, toggleLeftPanel } = useUIStore();
  const { selectedSectionId, getSection } = useBuilderStore();

  const { selectSection } = useBuilderStore();
  const section = selectedSectionId ? getSection(selectedSectionId) : undefined;
  const isEditing = !!section;
  const isContainer = section?.section_type === 'container';

  // Collapsed state — show just a thin expand bar
  if (!leftPanelOpen) {
    return (
      <div className="npb-left-panel npb-left-panel--collapsed">
        <button
          type="button"
          className="npb-panel-expand-btn"
          onClick={toggleLeftPanel}
          title="Expand panel"
        >
          <ChevronRight size={14} />
        </button>
      </div>
    );
  }

  return (
    <div className="npb-left-panel">
      {/* Collapse toggle on the right edge */}
      <button
        type="button"
        className="npb-panel-collapse-btn"
        onClick={toggleLeftPanel}
        title="Collapse panel"
      >
        <ChevronLeft size={12} />
      </button>

      {isEditing ? (
        /* ─── EDIT MODE: show section editor ─── */
        <>
          {/* Header with back button */}
          <div className="npb-edit-header" style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <button
              type="button"
              onClick={() => selectSection(null)}
              title="Back to components"
              style={{
                background: 'none', border: 'none', color: '#a1a1aa', cursor: 'pointer',
                padding: 4, borderRadius: 4, display: 'flex', alignItems: 'center',
              }}
              onMouseEnter={(e) => { (e.target as HTMLElement).style.color = '#fff'; }}
              onMouseLeave={(e) => { (e.target as HTMLElement).style.color = '#a1a1aa'; }}
            >
              <ArrowLeft size={16} />
            </button>
            <span className="npb-edit-header__title" style={{ flex: 1 }}>
              Edit {section.section_type.replace(/-/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())}
            </span>
          </div>

          {/* Tabs — icon + label */}
          <div className="npb-edit-tabs">
            {getEditTabs(isContainer).map((tab) => {
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
            <ErrorBoundary name={`Editor:${rightTab}`}>
              {rightTab === 'content' && (() => { const C = getEditor(section.section_type, 'content'); return <C section={section} />; })()}
              {rightTab === 'style' && (() => { const C = getEditor(section.section_type, 'style'); return <C section={section} />; })()}
              {rightTab === 'advanced' && <AdvancedEditor section={section} />}
            </ErrorBoundary>
          </div>
        </>
      ) : (
        /* ─── WIDGET MODE: show component palette / navigator ─── */
        <>
          <div className="npb-panel-tabs">
            <button
              className={`npb-panel-tab ${leftTab === 'components' ? 'npb-panel-tab--active' : ''}`}
              onClick={() => setLeftTab('components')}
              type="button"
            >
              Components
            </button>
            <button
              className={`npb-panel-tab ${leftTab === 'navigator' ? 'npb-panel-tab--active' : ''}`}
              onClick={() => setLeftTab('navigator')}
              type="button"
            >
              Navigator
            </button>
          </div>

          <div className="npb-left-panel__content">
            {leftTab === 'components' ? <ComponentPalette /> : <ElementNavigator />}
          </div>
        </>
      )}
    </div>
  );
}
