import { create } from 'zustand';
import type { Section } from '../types/builder';

interface HistoryState {
  past: Section[][];
  future: Section[][];
  maxHistory: number;

  pushState: (sections: Section[]) => void;
  undo: () => Section[] | null;
  redo: () => Section[] | null;
  canUndo: () => boolean;
  canRedo: () => boolean;
  clear: () => void;
}

export const useHistoryStore = create<HistoryState>((set, get) => ({
  past: [],
  future: [],
  maxHistory: 50,

  pushState: (sections) => {
    set((state) => {
      const past = [...state.past, sections.map((s) => ({ ...s }))];
      if (past.length > state.maxHistory) {
        past.shift();
      }
      return { past, future: [] };
    });
  },

  undo: () => {
    const state = get();
    if (state.past.length === 0) return null;

    const past = [...state.past];
    const previous = past.pop()!;

    // We need the current state to push to future — caller provides it
    set({ past });
    return previous;
  },

  redo: () => {
    const state = get();
    if (state.future.length === 0) return null;

    const future = [...state.future];
    const next = future.pop()!;

    set({ future });
    return next;
  },

  canUndo: () => get().past.length > 0,
  canRedo: () => get().future.length > 0,

  clear: () => set({ past: [], future: [] }),
}));

/**
 * Perform undo: restores previous section state.
 * Call from builderStore or keyboard shortcut handler.
 */
export function performUndo(
  currentSections: Section[],
  setSections: (s: Section[]) => void,
) {
  const history = useHistoryStore.getState();
  if (!history.canUndo()) return;

  // Push current state to future
  useHistoryStore.setState((state) => ({
    future: [...state.future, currentSections.map((s) => ({ ...s }))],
  }));

  const previous = history.undo();
  if (previous) {
    setSections(previous);
  }
}

/**
 * Perform redo: restores next section state.
 */
export function performRedo(
  currentSections: Section[],
  setSections: (s: Section[]) => void,
) {
  const history = useHistoryStore.getState();
  if (!history.canRedo()) return;

  // Push current state to past
  useHistoryStore.setState((state) => ({
    past: [...state.past, currentSections.map((s) => ({ ...s }))],
  }));

  const next = history.redo();
  if (next) {
    setSections(next);
  }
}
