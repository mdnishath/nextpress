import { useCallback, useState } from '@wordpress/element';
import {
  ArrowLeft,
  Undo2,
  Redo2,
  Monitor,
  Tablet,
  Smartphone,
  Eye,
  Save,
  Palette,
} from 'lucide-react';
import {
  BUILDER_THEMES,
  applyTheme,
  createCustomTheme,
  getSavedThemeId,
  saveThemeId,
  getCustomAccent,
  saveCustomAccent,
} from '../utils/builderThemes';
import { useBuilderStore } from '../store/builderStore';
import { useHistoryStore } from '../store/historyStore';
import { performUndo, performRedo } from '../store/historyStore';
import { useUIStore } from '../store/uiStore';
import { savePageChanges } from '../api/savePage';
import type { Breakpoint } from '../types/builder';
import { BREAKPOINTS } from '../utils/constants';
import { timeAgo } from '../utils/helpers';

const breakpointIcons: Record<Breakpoint, typeof Monitor> = {
  desktop: Monitor,
  tablet: Tablet,
  mobile: Smartphone,
};

export function Toolbar() {
  const {
    pageId,
    pageTitle,
    pageSlug,
    breakpoint,
    setBreakpoint,
    isDirty,
    isSaving,
    lastSaved,
    sections,
    originalSections,
    setSections,
    setSaving,
    markSaved,
    snapshotOriginal,
  } = useBuilderStore();

  const { canUndo, canRedo } = useHistoryStore();
  const { previewMode, setPreviewMode, addToast } = useUIStore();

  const handleSave = useCallback(async () => {
    if (!pageId || isSaving) return;
    setSaving(true);

    const result = await savePageChanges(pageId, pageSlug, sections, originalSections);

    if (result.success) {
      if (result.freshSections) {
        setSections(result.freshSections);
      }
      markSaved();
      snapshotOriginal();
      addToast('Page saved successfully', 'success');
    } else {
      setSaving(false);
      addToast(result.error || 'Failed to save', 'error');
    }
  }, [pageId, pageSlug, sections, originalSections, isSaving, setSaving, setSections, markSaved, snapshotOriginal, addToast]);

  const handleUndo = useCallback(() => {
    performUndo(sections, setSections);
  }, [sections, setSections]);

  const handleRedo = useCallback(() => {
    performRedo(sections, setSections);
  }, [sections, setSections]);

  const handleBack = () => {
    if (isDirty) {
      if (!window.confirm('You have unsaved changes. Leave anyway?')) return;
    }
    // Navigate back to admin dashboard
    const url = new URL(window.location.href);
    url.searchParams.delete('edit');
    window.location.href = url.toString();
  };

  // Keyboard shortcuts
  const handleKeyDown = useCallback(
    (e: KeyboardEvent) => {
      const isCtrl = e.ctrlKey || e.metaKey;

      if (isCtrl && e.key === 's') {
        e.preventDefault();
        handleSave();
      }
      if (isCtrl && !e.shiftKey && e.key === 'z') {
        e.preventDefault();
        handleUndo();
      }
      if (isCtrl && e.shiftKey && e.key === 'z') {
        e.preventDefault();
        handleRedo();
      }
    },
    [handleSave, handleUndo, handleRedo],
  );

  return (
    <div className="npb-toolbar">
      {/* Left: Back + Page title */}
      <div className="npb-toolbar__left">
        <button className="npb-toolbar-btn" onClick={handleBack} title="Back to pages">
          <ArrowLeft size={18} />
        </button>
        <div className="npb-toolbar-divider" />
        <span style={{ fontWeight: 600, fontSize: 14 }}>{pageTitle || 'Untitled Page'}</span>
        {isSaving ? (
          <span className="npb-save-indicator npb-save-indicator--saving">
            <span className="npb-save-spinner" />
            Saving...
          </span>
        ) : isDirty ? (
          <span className="npb-save-indicator npb-save-indicator--unsaved">
            <span className="npb-save-dot" />
            Unsaved
          </span>
        ) : null}
      </div>

      {/* Center: Undo/Redo + Responsive toggles */}
      <div className="npb-toolbar__center">
        <button
          className="npb-toolbar-btn"
          onClick={handleUndo}
          disabled={!canUndo()}
          title="Undo (Ctrl+Z)"
        >
          <Undo2 size={16} />
        </button>
        <button
          className="npb-toolbar-btn"
          onClick={handleRedo}
          disabled={!canRedo()}
          title="Redo (Ctrl+Shift+Z)"
        >
          <Redo2 size={16} />
        </button>

        <div className="npb-toolbar-divider" />

        {(Object.keys(BREAKPOINTS) as Breakpoint[]).map((bp) => {
          const Icon = breakpointIcons[bp];
          return (
            <button
              key={bp}
              className={`npb-toolbar-btn ${breakpoint === bp ? 'npb-toolbar-btn--active' : ''}`}
              onClick={() => setBreakpoint(bp)}
              title={`${BREAKPOINTS[bp].label} (${BREAKPOINTS[bp].width}px)`}
            >
              <Icon size={16} />
            </button>
          );
        })}
      </div>

      {/* Right: Preview + Save */}
      <div className="npb-toolbar__right">
        {lastSaved && (
          <span className="npb-toolbar__save-status">Saved {timeAgo(lastSaved)}</span>
        )}

        <ThemeSwitcher />

        <button
          className={`npb-toolbar-btn ${previewMode ? 'npb-toolbar-btn--active' : ''}`}
          onClick={() => setPreviewMode(!previewMode)}
          title="Toggle preview mode"
        >
          <Eye size={16} />
          Preview
        </button>

        <button
          className="npb-toolbar-btn npb-toolbar-btn--primary"
          onClick={handleSave}
          disabled={isSaving || !isDirty}
        >
          <Save size={16} />
          {isSaving ? 'Saving...' : 'Save'}
        </button>
      </div>
    </div>
  );
}

/** Theme switcher dropdown */
function ThemeSwitcher() {
  const [open, setOpen] = useState(false);
  const [activeId, setActiveId] = useState(getSavedThemeId());
  const [customColor, setCustomColor] = useState(getCustomAccent());

  const selectTheme = (id: string) => {
    setActiveId(id);
    saveThemeId(id);
    if (id === 'custom') {
      applyTheme(createCustomTheme(customColor));
    } else {
      const theme = BUILDER_THEMES.find((t) => t.id === id);
      if (theme) applyTheme(theme);
    }
  };

  const handleCustomColor = (color: string) => {
    setCustomColor(color);
    saveCustomAccent(color);
    setActiveId('custom');
    saveThemeId('custom');
    applyTheme(createCustomTheme(color));
  };

  return (
    <div style={{ position: 'relative' }}>
      <button
        className="npb-toolbar-btn"
        onClick={() => setOpen(!open)}
        title="Builder theme"
      >
        <Palette size={16} />
      </button>

      {open && (
        <>
          <div
            style={{ position: 'fixed', top: 0, right: 0, bottom: 0, left: 0, zIndex: 99 }}
            onClick={() => setOpen(false)}
          />
          <div style={{
            position: 'absolute', top: '100%', right: 0, marginTop: 8,
            background: '#1a1a2e', border: '1px solid rgba(255,255,255,0.12)',
            borderRadius: 8, padding: 12, width: 200, zIndex: 100,
            boxShadow: '0 8px 24px rgba(0,0,0,0.4)',
          }}>
            <div style={{ fontSize: 11, fontWeight: 700, color: 'rgba(255,255,255,0.5)', marginBottom: 8, textTransform: 'uppercase', letterSpacing: '0.05em' }}>
              Builder Theme
            </div>

            {BUILDER_THEMES.map((theme) => (
              <button
                key={theme.id}
                onClick={() => selectTheme(theme.id)}
                style={{
                  display: 'flex', alignItems: 'center', gap: 10, width: '100%',
                  padding: '8px 10px', border: 'none', borderRadius: 6,
                  background: activeId === theme.id ? 'rgba(255,255,255,0.1)' : 'transparent',
                  cursor: 'pointer', textAlign: 'left', color: '#e0e0e0',
                  fontSize: 13, fontFamily: 'var(--npb-font)',
                  transition: 'background 0.1s',
                }}
              >
                <div style={{ display: 'flex', gap: 3 }}>
                  <div style={{ width: 14, height: 14, borderRadius: 3, background: theme.sidebarBg, border: '1px solid rgba(255,255,255,0.15)' }} />
                  <div style={{ width: 14, height: 14, borderRadius: 3, background: theme.primary }} />
                </div>
                <span>{theme.name}</span>
                {activeId === theme.id && <span style={{ marginLeft: 'auto', fontSize: 11, color: theme.primary }}>✓</span>}
              </button>
            ))}

            {/* Custom accent */}
            <div style={{ borderTop: '1px solid rgba(255,255,255,0.08)', marginTop: 8, paddingTop: 8 }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                <span style={{ fontSize: 12, color: 'rgba(255,255,255,0.5)' }}>Custom</span>
                <div style={{ position: 'relative', width: 24, height: 24, borderRadius: 4, background: customColor, border: '1px solid rgba(255,255,255,0.15)', cursor: 'pointer', overflow: 'hidden' }}>
                  <input
                    type="color"
                    value={customColor}
                    onChange={(e) => handleCustomColor(e.target.value)}
                    style={{ position: 'absolute', top: 0, right: 0, bottom: 0, left: 0, width: '100%', height: '100%', opacity: 0, cursor: 'pointer' }}
                  />
                </div>
                {activeId === 'custom' && <span style={{ fontSize: 11, color: customColor }}>✓</span>}
              </div>
            </div>
          </div>
        </>
      )}
    </div>
  );
}
