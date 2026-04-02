/**
 * Dynamically load Google Fonts into the page.
 * Caches loaded fonts to avoid duplicate requests.
 */

const loadedFonts = new Set<string>();

export function loadGoogleFont(fontFamily: string): void {
  if (!fontFamily || fontFamily === 'Default' || fontFamily === '') return;
  if (loadedFonts.has(fontFamily)) return;

  loadedFonts.add(fontFamily);

  const encoded = fontFamily.replace(/\s+/g, '+');
  const link = document.createElement('link');
  link.rel = 'stylesheet';
  link.href = `https://fonts.googleapis.com/css2?family=${encoded}:wght@300;400;500;600;700;800;900&display=swap`;
  document.head.appendChild(link);
}

/**
 * Load all Google Fonts used across all sections.
 * Call this once on builder load to ensure saved fonts are available.
 */
export function loadAllUsedFonts(sections: { style: Record<string, unknown> }[]): void {
  const fonts = new Set<string>();
  for (const section of sections) {
    const ff = section.style?.fontFamily;
    if (typeof ff === 'string' && ff) {
      fonts.add(ff);
    }
  }
  fonts.forEach(loadGoogleFont);
}
