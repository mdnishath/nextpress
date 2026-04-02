/**
 * Builder UI Theme System
 * Users can change the builder's visual appearance (toolbar, panels, accent color).
 * Stored in localStorage, applied via CSS custom properties.
 */

export interface BuilderTheme {
  id: string;
  name: string;
  sidebarBg: string;
  sidebarText: string;
  sidebarTextMuted: string;
  toolbarBg: string;
  canvasBg: string;
  primary: string;
  primaryHover: string;
  selectedLight: string;
  editPanelBg: string;
}

export const BUILDER_THEMES: BuilderTheme[] = [
  {
    id: 'dark',
    name: 'Dark',
    sidebarBg: '#09090b',
    sidebarText: '#fafafa',
    sidebarTextMuted: '#a1a1aa',
    toolbarBg: '#09090b',
    canvasBg: '#f4f4f5',
    primary: '#7c3aed',
    primaryHover: '#6d28d9',
    selectedLight: '#ede9fe',
    editPanelBg: '#1a1a2e',
  },
  {
    id: 'light',
    name: 'Light',
    sidebarBg: '#ffffff',
    sidebarText: '#09090b',
    sidebarTextMuted: '#6b7280',
    toolbarBg: '#f8fafc',
    canvasBg: '#f1f5f9',
    primary: '#7c3aed',
    primaryHover: '#6d28d9',
    selectedLight: '#ede9fe',
    editPanelBg: '#f8fafc',
  },
  {
    id: 'midnight',
    name: 'Midnight Blue',
    sidebarBg: '#0f172a',
    sidebarText: '#e2e8f0',
    sidebarTextMuted: '#64748b',
    toolbarBg: '#0f172a',
    canvasBg: '#f1f5f9',
    primary: '#3b82f6',
    primaryHover: '#2563eb',
    selectedLight: '#dbeafe',
    editPanelBg: '#1e293b',
  },
  {
    id: 'forest',
    name: 'Forest',
    sidebarBg: '#0f1a0f',
    sidebarText: '#d4e5d4',
    sidebarTextMuted: '#6b8a6b',
    toolbarBg: '#0f1a0f',
    canvasBg: '#f0fdf4',
    primary: '#22c55e',
    primaryHover: '#16a34a',
    selectedLight: '#dcfce7',
    editPanelBg: '#14291a',
  },
  {
    id: 'rose',
    name: 'Rose',
    sidebarBg: '#1a0a14',
    sidebarText: '#fce7f3',
    sidebarTextMuted: '#9d7a8e',
    toolbarBg: '#1a0a14',
    canvasBg: '#fff1f2',
    primary: '#e11d48',
    primaryHover: '#be123c',
    selectedLight: '#ffe4e6',
    editPanelBg: '#2a1020',
  },
];

const STORAGE_KEY = 'npb-builder-theme';
const CUSTOM_COLOR_KEY = 'npb-builder-custom-accent';

/** Get saved theme ID from localStorage */
export function getSavedThemeId(): string {
  try {
    return localStorage.getItem(STORAGE_KEY) || 'dark';
  } catch {
    return 'dark';
  }
}

/** Save theme to localStorage */
export function saveThemeId(id: string): void {
  try {
    localStorage.setItem(STORAGE_KEY, id);
  } catch {}
}

/** Get saved custom accent color */
export function getCustomAccent(): string {
  try {
    return localStorage.getItem(CUSTOM_COLOR_KEY) || '#7c3aed';
  } catch {
    return '#7c3aed';
  }
}

/** Save custom accent color */
export function saveCustomAccent(color: string): void {
  try {
    localStorage.setItem(CUSTOM_COLOR_KEY, color);
  } catch {}
}

/** Apply theme CSS variables to the builder root element */
export function applyTheme(theme: BuilderTheme): void {
  const root = document.querySelector('.npb-builder') as HTMLElement;
  if (!root) return;

  root.style.setProperty('--npb-sidebar-bg', theme.sidebarBg);
  root.style.setProperty('--npb-sidebar-text', theme.sidebarText);
  root.style.setProperty('--npb-sidebar-text-muted', theme.sidebarTextMuted);
  root.style.setProperty('--npb-canvas-bg', theme.canvasBg);
  root.style.setProperty('--npb-primary', theme.primary);
  root.style.setProperty('--npb-primary-hover', theme.primaryHover);
  root.style.setProperty('--npb-selected', theme.primary);
  root.style.setProperty('--npb-selected-light', theme.selectedLight);

  // Toolbar bg
  const toolbar = root.querySelector('.npb-toolbar') as HTMLElement;
  if (toolbar) toolbar.style.background = theme.toolbarBg;

  // Edit panel bg (left panel in edit mode)
  const editPanelBg = theme.editPanelBg;
  root.style.setProperty('--npb-edit-panel-bg', editPanelBg);

  // For light themes, adjust edit panel text colors
  const isLight = theme.id === 'light';
  if (isLight) {
    root.querySelectorAll('.el-control__label').forEach((el) => {
      (el as HTMLElement).style.color = '#374151';
    });
    root.querySelectorAll('.el-section__title').forEach((el) => {
      (el as HTMLElement).style.color = '#09090b';
    });
  }
}

/** Generate a custom theme from an accent color */
export function createCustomTheme(accent: string): BuilderTheme {
  // Lighten the accent for selectedLight
  const hex = accent.replace('#', '');
  const r = parseInt(hex.substring(0, 2), 16);
  const g = parseInt(hex.substring(2, 4), 16);
  const b = parseInt(hex.substring(4, 6), 16);
  const light = `rgba(${r}, ${g}, ${b}, 0.1)`;

  return {
    id: 'custom',
    name: 'Custom',
    sidebarBg: '#09090b',
    sidebarText: '#fafafa',
    sidebarTextMuted: '#a1a1aa',
    toolbarBg: '#09090b',
    canvasBg: '#f4f4f5',
    primary: accent,
    primaryHover: accent,
    selectedLight: light,
    editPanelBg: '#1a1a2e',
  };
}

/** Initialize theme on builder load */
export function initBuilderTheme(): void {
  const themeId = getSavedThemeId();
  if (themeId === 'custom') {
    applyTheme(createCustomTheme(getCustomAccent()));
  } else {
    const theme = BUILDER_THEMES.find((t) => t.id === themeId);
    if (theme) applyTheme(theme);
  }
}
