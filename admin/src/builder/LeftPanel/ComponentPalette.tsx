import { useState, useMemo } from '@wordpress/element';
import { ComponentCard } from './ComponentCard';
import { useComponents } from '../../api/useComponents';
import type { Component } from '../../types/builder';

export function ComponentPalette() {
  // TODO: Re-enable API-based component loading once section components are migrated
  // const { components, loading } = useComponents();
  const { components: apiComponents } = useComponents();
  const [search, setSearch] = useState('');

  // Only show active components in palette
  const allComponents = useMemo(() => {
    return apiComponents.filter((c) => {
      const active = c.is_active;
      return active === undefined || active === true || active === 1 || active === '1';
    });
  }, [apiComponents]);

  const filtered = useMemo(() => {
    if (!search.trim()) return allComponents;
    const q = search.toLowerCase();
    return allComponents.filter(
      (c) =>
        c.name.toLowerCase().includes(q) ||
        c.category.toLowerCase().includes(q) ||
        c.slug.toLowerCase().includes(q),
    );
  }, [allComponents, search]);

  const groupedByCategory = useMemo(() => {
    const groups: Record<string, Component[]> = {};
    for (const comp of filtered) {
      const cat = comp.category || 'other';
      if (!groups[cat]) groups[cat] = [];
      groups[cat].push(comp);
    }
    // Sort: structure first, then alphabetical
    const sorted: Record<string, Component[]> = {};
    if (groups['structure']) { sorted['structure'] = groups['structure']; delete groups['structure']; }
    for (const key of Object.keys(groups).sort()) { sorted[key] = groups[key]; }
    return sorted;
  }, [filtered]);

  return (
    <div>
      <input
        type="text"
        className="npb-palette-search"
        placeholder="Search components..."
        value={search}
        onChange={(e) => setSearch(e.target.value)}
      />

      {Object.entries(groupedByCategory).map(([category, comps]) => (
        <div key={category} className="npb-palette-category">
          <div className="npb-palette-category__label">
            {category === 'structure' ? 'Layout' : category}
          </div>
          <div className="npb-palette-grid">
            {comps.map((comp) => (
              <ComponentCard key={comp.slug} component={comp} />
            ))}
          </div>
        </div>
      ))}

      {filtered.length === 0 && (
        <div style={{ padding: 24, textAlign: 'center', color: '#a1a1aa', fontSize: 13 }}>
          No components found
        </div>
      )}
    </div>
  );
}
