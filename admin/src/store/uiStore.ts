import { create } from 'zustand';

type LeftTab = 'components' | 'navigator';
type RightTab = 'content' | 'style' | 'advanced';

interface Toast {
  id: string;
  message: string;
  type: 'success' | 'error' | 'info';
}

interface UIState {
  // Panels
  leftPanelOpen: boolean;
  rightPanelOpen: boolean;
  leftTab: LeftTab;
  rightTab: RightTab;

  // Preview mode (hides builder controls)
  previewMode: boolean;

  // Toasts
  toasts: Toast[];

  // Confirmation dialog
  confirmDialog: {
    open: boolean;
    title: string;
    message: string;
    onConfirm: (() => void) | null;
  };

  // Clipboard (for copy/paste sections)
  clipboardSection: string | null; // section JSON

  // Actions
  setLeftTab: (tab: LeftTab) => void;
  setRightTab: (tab: RightTab) => void;
  toggleLeftPanel: () => void;
  toggleRightPanel: () => void;
  setPreviewMode: (mode: boolean) => void;

  addToast: (message: string, type?: Toast['type']) => void;
  removeToast: (id: string) => void;

  showConfirm: (title: string, message: string, onConfirm: () => void) => void;
  hideConfirm: () => void;

  setClipboard: (sectionJson: string | null) => void;
}

export const useUIStore = create<UIState>((set) => ({
  leftPanelOpen: true,
  rightPanelOpen: true,
  leftTab: 'components',
  rightTab: 'content',
  previewMode: false,
  toasts: [],
  confirmDialog: { open: false, title: '', message: '', onConfirm: null },
  clipboardSection: null,

  setLeftTab: (tab) => set({ leftTab: tab }),
  setRightTab: (tab) => set({ rightTab: tab }),
  toggleLeftPanel: () => set((s) => ({ leftPanelOpen: !s.leftPanelOpen })),
  toggleRightPanel: () => set((s) => ({ rightPanelOpen: !s.rightPanelOpen })),
  setPreviewMode: (mode) => set({ previewMode: mode }),

  addToast: (message, type = 'info') => {
    const id = `toast_${Date.now()}`;
    set((s) => ({ toasts: [...s.toasts, { id, message, type }] }));
    // Auto-remove after 4 seconds
    setTimeout(() => {
      set((s) => ({ toasts: s.toasts.filter((t) => t.id !== id) }));
    }, 4000);
  },

  removeToast: (id) => set((s) => ({ toasts: s.toasts.filter((t) => t.id !== id) })),

  showConfirm: (title, message, onConfirm) =>
    set({ confirmDialog: { open: true, title, message, onConfirm } }),

  hideConfirm: () =>
    set({ confirmDialog: { open: false, title: '', message: '', onConfirm: null } }),

  setClipboard: (sectionJson) => set({ clipboardSection: sectionJson }),
}));
