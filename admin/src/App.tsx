import { useState, useEffect, useCallback } from '@wordpress/element';
import { PageBuilder } from './builder/PageBuilder';
import { apiGet, apiPost, apiPut, apiDelete } from './api/useApi';

/**
 * Page type as returned by the list API.
 */
interface PageItem {
  id: number;
  title: string;
  slug: string;
  status: string;
  page_type?: string;
}

/**
 * Get current admin page slug and edit param from URL.
 */
function getRouteInfo() {
  const params = new URLSearchParams(window.location.search);
  return {
    page: params.get('page') || 'nextpress',
    editSlug: params.get('edit') || null,
  };
}

/**
 * Root App component.
 * Routes based on ?page= URL param to show the correct admin sub-page.
 * If ?edit=<slug> is present, mounts full-screen page builder.
 */
export default function App() {
  const { page, editSlug } = getRouteInfo();

  // Full-screen builder mode — edit param is the page slug
  if (editSlug) {
    return <PageBuilder pageSlug={editSlug} />;
  }

  // Route to React-powered admin sub-pages.
  // Other pages (Forms, Components, Templates, Theme, SEO, Navigation, Settings)
  // still use the PHP dashboard — React doesn't mount on those pages.
  switch (page) {
    case 'nextpress':
      return <DashboardPage />;
    case 'nextpress-pages':
      return <PagesListPage pageType="page" title="Pages" />;
    case 'nextpress-headers':
      return <PagesListPage pageType="header" title="Headers" />;
    case 'nextpress-footers':
      return <PagesListPage pageType="footer" title="Footers" />;
    case 'nextpress-components':
      return <ComponentsPage />;
    default:
      // PHP dashboard handles this page — render nothing
      return null;
  }
}

/**
 * Dashboard — overview with quick links.
 */
function DashboardPage() {
  return (
    <AdminLayout title="Dashboard" subtitle="Welcome to NextPress Builder">
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(220px, 1fr))', gap: 12, marginBottom: 32 }}>
        {[
          { label: 'Pages', slug: 'nextpress-pages', icon: 'dashicons-admin-page' },
          { label: 'Headers', slug: 'nextpress-headers', icon: 'dashicons-align-center' },
          { label: 'Footers', slug: 'nextpress-footers', icon: 'dashicons-align-full-width' },
          { label: 'Forms', slug: 'nextpress-forms', icon: 'dashicons-feedback' },
          { label: 'Components', slug: 'nextpress-components', icon: 'dashicons-screenoptions' },
          { label: 'Templates', slug: 'nextpress-templates', icon: 'dashicons-layout' },
          { label: 'Theme', slug: 'nextpress-theme', icon: 'dashicons-art' },
          { label: 'SEO', slug: 'nextpress-seo', icon: 'dashicons-search' },
          { label: 'Navigation', slug: 'nextpress-navigation', icon: 'dashicons-menu' },
          { label: 'Settings', slug: 'nextpress-settings', icon: 'dashicons-admin-settings' },
        ].map((item) => (
          <a
            key={item.slug}
            href={`admin.php?page=${item.slug}`}
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: 12,
              padding: '14px 16px',
              background: '#fff',
              border: '1px solid #e5e7eb',
              borderRadius: 10,
              textDecoration: 'none',
              color: '#374151',
              fontSize: 13,
              fontWeight: 600,
              transition: 'all 0.15s ease',
            }}
          >
            <span className={`dashicons ${item.icon}`} style={{ fontSize: 18, width: 18, height: 18, color: '#7c3aed' }} />
            {item.label}
          </a>
        ))}
      </div>
      <PagesTable pageType="page" title="Recent Pages" />
    </AdminLayout>
  );
}

/**
 * Pages list page — used for Pages, Headers, and Footers.
 */
function PagesListPage({ pageType, title }: { pageType: string; title: string }) {
  return (
    <AdminLayout title={title} subtitle={`Manage your ${title.toLowerCase()}`}>
      <PagesTable pageType={pageType} title={title} showCreate />
    </AdminLayout>
  );
}

/**
 * Shared admin page layout wrapper.
 */
function AdminLayout({
  title,
  subtitle,
  children,
}: {
  title: string;
  subtitle?: string;
  children: React.ReactNode;
}) {
  return (
    <div style={{ padding: 24, maxWidth: 1200, margin: '0 auto' }}>
      <div style={{ marginBottom: 24 }}>
        <h1 style={{ fontSize: 24, fontWeight: 700, marginBottom: 4, color: '#09090b' }}>{title}</h1>
        {subtitle && <p style={{ color: '#6b7280', fontSize: 14, margin: 0 }}>{subtitle}</p>}
      </div>
      {children}
    </div>
  );
}

/**
 * Reusable pages table with create, edit, and delete.
 */
function PagesTable({
  pageType,
  title,
  showCreate,
}: {
  pageType: string;
  title: string;
  showCreate?: boolean;
}) {
  const [pages, setPages] = useState<PageItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [newTitle, setNewTitle] = useState('');
  const [newSlug, setNewSlug] = useState('');
  const [creating, setCreating] = useState(false);
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);

  const fetchPages = useCallback(() => {
    setLoading(true);
    apiGet<PageItem[]>(`/pages?type=${pageType}&status=all`)
      .then((res) => {
        const list = Array.isArray(res) ? res : (res as unknown as { data: PageItem[] }).data ?? [];
        setPages(list);
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, [pageType]);

  useEffect(() => { fetchPages(); }, [fetchPages]);

  // Auto-generate slug from title
  const handleTitleChange = (val: string) => {
    setNewTitle(val);
    setNewSlug(val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''));
  };

  const handleCreate = async () => {
    if (!newTitle.trim()) return;
    setCreating(true);
    setError(null);
    try {
      const body: Record<string, unknown> = { title: newTitle.trim(), slug: newSlug.trim() || undefined };
      if (pageType !== 'page') body.page_type = pageType;
      await apiPost('/pages', body);
      setNewTitle('');
      setNewSlug('');
      setShowCreateForm(false);
      fetchPages();
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to create');
    } finally {
      setCreating(false);
    }
  };

  const handleDelete = async (page: PageItem) => {
    if (!window.confirm(`Delete "${page.title}"? This cannot be undone.`)) return;
    setDeletingId(page.id);
    try {
      await apiDelete(`/pages/${page.id}`);
      setPages((prev) => prev.filter((p) => p.id !== page.id));
    } catch (err: unknown) {
      alert(err instanceof Error ? err.message : 'Failed to delete');
    } finally {
      setDeletingId(null);
    }
  };

  const handleTogglePublish = async (page: PageItem) => {
    const action = page.status === 'published' ? 'unpublish' : 'publish';
    try {
      await apiPut(`/pages/${page.id}/${action}`, {});
      setPages((prev) =>
        prev.map((p) =>
          p.id === page.id ? { ...p, status: action === 'publish' ? 'published' : 'draft' } : p,
        ),
      );
    } catch (err: unknown) {
      alert(err instanceof Error ? err.message : `Failed to ${action}`);
    }
  };

  if (loading) {
    return <p style={{ color: '#6b7280' }}>Loading {title.toLowerCase()}...</p>;
  }

  const currentPage = new URLSearchParams(window.location.search).get('page') || 'nextpress-pages';

  const thStyle: React.CSSProperties = {
    padding: '10px 16px', fontSize: 12, fontWeight: 600, color: '#6b7280',
    textTransform: 'uppercase', letterSpacing: '0.03em',
  };

  const singularTitle = title.replace(/s$/, '');

  return (
    <div style={{ background: '#fff', border: '1px solid #e5e7eb', borderRadius: 12, overflow: 'hidden' }}>
      {/* Header bar with count + Add New button */}
      <div style={{
        padding: '12px 16px', borderBottom: '1px solid #e5e7eb',
        display: 'flex', justifyContent: 'space-between', alignItems: 'center',
      }}>
        <span style={{ fontSize: 14, fontWeight: 600, color: '#09090b' }}>
          {pages.length} {title.toLowerCase()}
        </span>
        {showCreate && (
          <button
            onClick={() => setShowCreateForm(!showCreateForm)}
            style={{
              padding: '7px 16px', background: '#7c3aed', color: '#fff',
              border: 'none', borderRadius: 6, fontSize: 13, fontWeight: 600,
              cursor: 'pointer',
            }}
          >
            + Add New {singularTitle}
          </button>
        )}
      </div>

      {/* Create form (inline) */}
      {showCreateForm && (
        <div style={{
          padding: 16, borderBottom: '1px solid #e5e7eb', background: '#fafafa',
          display: 'flex', gap: 10, alignItems: 'flex-end', flexWrap: 'wrap',
        }}>
          <div style={{ flex: '1 1 200px' }}>
            <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: '#6b7280', marginBottom: 4, textTransform: 'uppercase', letterSpacing: '0.03em' }}>
              Title
            </label>
            <input
              type="text"
              value={newTitle}
              onChange={(e) => handleTitleChange(e.target.value)}
              placeholder={`My ${singularTitle}`}
              autoFocus
              onKeyDown={(e) => e.key === 'Enter' && handleCreate()}
              style={{
                width: '100%', padding: '8px 12px', border: '1px solid #d1d5db',
                borderRadius: 6, fontSize: 14, outline: 'none',
              }}
            />
          </div>
          <div style={{ flex: '1 1 200px' }}>
            <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: '#6b7280', marginBottom: 4, textTransform: 'uppercase', letterSpacing: '0.03em' }}>
              Slug
            </label>
            <input
              type="text"
              value={newSlug}
              onChange={(e) => setNewSlug(e.target.value)}
              placeholder="auto-generated"
              onKeyDown={(e) => e.key === 'Enter' && handleCreate()}
              style={{
                width: '100%', padding: '8px 12px', border: '1px solid #d1d5db',
                borderRadius: 6, fontSize: 14, outline: 'none', fontFamily: 'monospace',
              }}
            />
          </div>
          <div style={{ display: 'flex', gap: 8 }}>
            <button
              onClick={handleCreate}
              disabled={creating || !newTitle.trim()}
              style={{
                padding: '8px 20px', background: creating ? '#a78bfa' : '#7c3aed',
                color: '#fff', border: 'none', borderRadius: 6, fontSize: 13,
                fontWeight: 600, cursor: creating ? 'not-allowed' : 'pointer',
              }}
            >
              {creating ? 'Creating...' : 'Create'}
            </button>
            <button
              onClick={() => { setShowCreateForm(false); setNewTitle(''); setNewSlug(''); setError(null); }}
              style={{
                padding: '8px 16px', background: '#f3f4f6', color: '#374151',
                border: 'none', borderRadius: 6, fontSize: 13, fontWeight: 500, cursor: 'pointer',
              }}
            >
              Cancel
            </button>
          </div>
          {error && (
            <div style={{ width: '100%', color: '#ef4444', fontSize: 13, marginTop: 4 }}>
              {error}
            </div>
          )}
        </div>
      )}

      {/* Empty state */}
      {pages.length === 0 ? (
        <div style={{ padding: 48, textAlign: 'center', color: '#a1a1aa' }}>
          <p style={{ fontSize: 15, marginBottom: 8 }}>No {title.toLowerCase()} yet.</p>
          {showCreate && (
            <p style={{ fontSize: 13 }}>Click "Add New {singularTitle}" above to get started.</p>
          )}
        </div>
      ) : (
        <table style={{ width: '100%', borderCollapse: 'collapse' }}>
          <thead>
            <tr style={{ textAlign: 'left', borderBottom: '2px solid #f3f4f6', background: '#fafafa' }}>
              <th style={thStyle}>Title</th>
              <th style={thStyle}>Slug</th>
              <th style={thStyle}>Status</th>
              <th style={{ ...thStyle, textAlign: 'right' }}>Actions</th>
            </tr>
          </thead>
          <tbody>
            {pages.map((page) => (
              <tr
                key={page.id}
                style={{
                  borderBottom: '1px solid #f3f4f6',
                  opacity: deletingId === page.id ? 0.4 : 1,
                  transition: 'opacity 0.15s',
                }}
              >
                <td style={{ padding: '10px 16px', fontWeight: 500 }}>{page.title}</td>
                <td style={{ padding: '10px 16px', color: '#6b7280', fontFamily: 'monospace', fontSize: 13 }}>
                  /{page.slug}
                </td>
                <td style={{ padding: '10px 16px' }}>
                  <span
                    style={{
                      padding: '2px 8px', borderRadius: 4, fontSize: 12, fontWeight: 600,
                      background: page.status === 'published' ? '#dcfce7' : '#f3f4f6',
                      color: page.status === 'published' ? '#16a34a' : '#6b7280',
                    }}
                  >
                    {page.status}
                  </span>
                </td>
                <td style={{ padding: '10px 16px', textAlign: 'right' }}>
                  <div style={{ display: 'flex', gap: 6, justifyContent: 'flex-end' }}>
                    <a
                      href={`admin.php?page=${currentPage}&edit=${page.slug}`}
                      style={{
                        padding: '6px 14px', background: '#7c3aed', color: '#fff',
                        borderRadius: 6, textDecoration: 'none', fontSize: 13,
                        fontWeight: 500, display: 'inline-block',
                      }}
                    >
                      Edit
                    </a>
                    <button
                      onClick={() => handleDelete(page)}
                      disabled={deletingId === page.id}
                      style={{
                        padding: '6px 12px', background: '#fff', color: '#ef4444',
                        border: '1px solid #fecaca', borderRadius: 6, fontSize: 13,
                        fontWeight: 500, cursor: deletingId === page.id ? 'not-allowed' : 'pointer',
                        transition: 'all 0.15s',
                      }}
                      onMouseEnter={(e) => {
                        (e.target as HTMLElement).style.background = '#fef2f2';
                        (e.target as HTMLElement).style.borderColor = '#ef4444';
                      }}
                      onMouseLeave={(e) => {
                        (e.target as HTMLElement).style.background = '#fff';
                        (e.target as HTMLElement).style.borderColor = '#fecaca';
                      }}
                    >
                      Delete
                    </button>
                    <button
                      onClick={() => handleTogglePublish(page)}
                      style={{
                        padding: '6px 12px', background: '#fff',
                        color: page.status === 'published' ? '#f59e0b' : '#16a34a',
                        border: `1px solid ${page.status === 'published' ? '#fde68a' : '#bbf7d0'}`,
                        borderRadius: 6, fontSize: 13,
                        fontWeight: 500, cursor: 'pointer',
                        transition: 'all 0.15s',
                      }}
                    >
                      {page.status === 'published' ? 'Unpublish' : 'Publish'}
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}

/**
 * Components management page — list, toggle active, edit.
 */
interface ComponentItem {
  id: number;
  slug: string;
  name: string;
  category: string;
  description: string;
  is_active: number;
  is_user_created: number;
  content_schema: unknown;
}

function ComponentsPage() {
  const [components, setComponents] = useState<ComponentItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [editName, setEditName] = useState('');
  const [editDesc, setEditDesc] = useState('');
  const [editCategory, setEditCategory] = useState('');
  const [editSchema, setEditSchema] = useState('');
  const [showCreate, setShowCreate] = useState(false);
  const [newName, setNewName] = useState('');
  const [newSlug, setNewSlug] = useState('');
  const [newCategory, setNewCategory] = useState('basic');
  const [newDesc, setNewDesc] = useState('');
  const [creating, setCreating] = useState(false);

  const fetchComponents = useCallback(() => {
    setLoading(true);
    apiGet<unknown>('/components')
      .then((res) => {
        const data = res as Record<string, unknown>;
        const inner = data.data as Record<string, unknown> | undefined;
        const comps = inner?.components;
        setComponents(Array.isArray(comps) ? comps as ComponentItem[] : []);
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => { fetchComponents(); }, [fetchComponents]);

  const handleToggle = async (comp: ComponentItem) => {
    try {
      await apiPost(`/components/${comp.id}/toggle`, {});
      setComponents((prev) =>
        prev.map((c) => c.id === comp.id ? { ...c, is_active: c.is_active ? 0 : 1 } : c)
      );
    } catch {
      alert('Failed to toggle');
    }
  };

  const handleEdit = (comp: ComponentItem) => {
    setEditingId(comp.id);
    setEditName(comp.name);
    setEditDesc(comp.description || '');
    setEditCategory(comp.category);
    try {
      setEditSchema(JSON.stringify(comp.content_schema, null, 2));
    } catch {
      setEditSchema('{"fields":[]}');
    }
  };

  const handleSaveEdit = async () => {
    if (!editingId) return;
    let parsedSchema;
    try { parsedSchema = JSON.parse(editSchema); } catch { alert('Invalid JSON in schema'); return; }
    try {
      await apiPut(`/components/${editingId}`, {
        name: editName,
        description: editDesc,
        category: editCategory,
        content_schema: parsedSchema,
      });
      setComponents((prev) =>
        prev.map((c) => c.id === editingId ? { ...c, name: editName, description: editDesc, category: editCategory, content_schema: parsedSchema } : c)
      );
      setEditingId(null);
    } catch {
      alert('Failed to save');
    }
  };

  const handleCreate = async () => {
    if (!newName.trim() || !newSlug.trim()) return;
    setCreating(true);
    try {
      await apiPost('/components', {
        name: newName.trim(),
        slug: newSlug.trim(),
        category: newCategory,
        description: newDesc,
        content_schema: { fields: [] },
      });
      setShowCreate(false);
      setNewName(''); setNewSlug(''); setNewDesc('');
      fetchComponents();
    } catch (err: unknown) {
      alert(err instanceof Error ? err.message : 'Failed to create');
    } finally {
      setCreating(false);
    }
  };

  const handleDelete = async (comp: ComponentItem) => {
    if (!window.confirm(`Delete "${comp.name}"? This cannot be undone.`)) return;
    try {
      await apiDelete(`/components/${comp.id}`);
      setComponents((prev) => prev.filter((c) => c.id !== comp.id));
    } catch {
      alert('Failed to delete');
    }
  };

  const activeCount = components.filter((c) => c.is_active).length;
  const categories = [...new Set(components.map((c) => c.category))];

  const thStyle2: React.CSSProperties = {
    padding: '10px 16px', fontSize: 12, fontWeight: 600, color: '#6b7280',
    textTransform: 'uppercase', letterSpacing: '0.03em',
  };

  return (
    <AdminLayout title="Components" subtitle="Manage your builder components — activate, deactivate, edit, or create custom">
      {/* Create button */}
      <div style={{ display: 'flex', justifyContent: 'flex-end', marginBottom: 16 }}>
        <button onClick={() => setShowCreate(!showCreate)} style={{
          padding: '8px 18px', background: '#7c3aed', color: '#fff', border: 'none',
          borderRadius: 6, fontSize: 13, fontWeight: 600, cursor: 'pointer',
        }}>
          + Create Custom Component
        </button>
      </div>

      {/* Create form */}
      {showCreate && (
        <div style={{ background: '#fff', border: '1px solid #e5e7eb', borderRadius: 12, padding: 20, marginBottom: 20 }}>
          <h3 style={{ margin: '0 0 16px', fontSize: 15, fontWeight: 700 }}>New Custom Component</h3>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 12 }}>
            <div>
              <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: '#6b7280', marginBottom: 4 }}>Name</label>
              <input type="text" value={newName} onChange={(e) => { setNewName(e.target.value); setNewSlug(e.target.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '')); }}
                placeholder="My Widget" style={{ width: '100%', padding: '8px 12px', border: '1px solid #d1d5db', borderRadius: 6, fontSize: 14, boxSizing: 'border-box' }} />
            </div>
            <div>
              <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: '#6b7280', marginBottom: 4 }}>Slug</label>
              <input type="text" value={newSlug} onChange={(e) => setNewSlug(e.target.value)}
                placeholder="my_widget" style={{ width: '100%', padding: '8px 12px', border: '1px solid #d1d5db', borderRadius: 6, fontSize: 14, fontFamily: 'monospace', boxSizing: 'border-box' }} />
            </div>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 12 }}>
            <div>
              <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: '#6b7280', marginBottom: 4 }}>Category</label>
              <select value={newCategory} onChange={(e) => setNewCategory(e.target.value)}
                style={{ width: '100%', padding: '8px 12px', border: '1px solid #d1d5db', borderRadius: 6, fontSize: 14, boxSizing: 'border-box' }}>
                <option value="basic">Basic</option>
                <option value="structure">Structure</option>
                <option value="content">Content</option>
                <option value="media">Media</option>
                <option value="interactive">Interactive</option>
                <option value="custom">Custom</option>
              </select>
            </div>
            <div>
              <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: '#6b7280', marginBottom: 4 }}>Description</label>
              <input type="text" value={newDesc} onChange={(e) => setNewDesc(e.target.value)}
                placeholder="Short description" style={{ width: '100%', padding: '8px 12px', border: '1px solid #d1d5db', borderRadius: 6, fontSize: 14, boxSizing: 'border-box' }} />
            </div>
          </div>
          <div style={{ display: 'flex', gap: 8, justifyContent: 'flex-end' }}>
            <button onClick={() => setShowCreate(false)} style={{ padding: '8px 16px', background: '#f3f4f6', border: 'none', borderRadius: 6, cursor: 'pointer' }}>Cancel</button>
            <button onClick={handleCreate} disabled={creating || !newName.trim()} style={{
              padding: '8px 18px', background: creating ? '#a78bfa' : '#7c3aed', color: '#fff',
              border: 'none', borderRadius: 6, fontSize: 13, fontWeight: 600, cursor: creating ? 'not-allowed' : 'pointer',
            }}>
              {creating ? 'Creating...' : 'Create Component'}
            </button>
          </div>
        </div>
      )}

      {/* Stats */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 16, marginBottom: 24 }}>
        {[
          { label: 'Total Components', value: components.length, bg: '#7c3aed' },
          { label: 'Active', value: activeCount, bg: '#22c55e' },
          { label: 'Categories', value: categories.length, bg: '#3b82f6' },
        ].map((s) => (
          <div key={s.label} style={{
            background: '#fff', border: '1px solid #e5e7eb', borderRadius: 10, padding: '16px 20px',
            display: 'flex', alignItems: 'center', gap: 12,
          }}>
            <div style={{ width: 40, height: 40, borderRadius: 8, background: `${s.bg}20`, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <div style={{ width: 14, height: 14, borderRadius: 3, background: s.bg }} />
            </div>
            <div>
              <div style={{ fontSize: 22, fontWeight: 700 }}>{s.value}</div>
              <div style={{ fontSize: 12, color: '#6b7280' }}>{s.label}</div>
            </div>
          </div>
        ))}
      </div>

      {loading ? (
        <p style={{ color: '#6b7280' }}>Loading components...</p>
      ) : components.length === 0 ? (
        <div style={{ background: '#fff', border: '1px solid #e5e7eb', borderRadius: 12, padding: 48, textAlign: 'center', color: '#a1a1aa' }}>
          No components found. They will be seeded on next page load.
        </div>
      ) : (
        <div style={{ background: '#fff', border: '1px solid #e5e7eb', borderRadius: 12, overflow: 'hidden' }}>
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ textAlign: 'left', borderBottom: '2px solid #f3f4f6', background: '#fafafa' }}>
                <th style={thStyle2}>Component</th>
                <th style={thStyle2}>Category</th>
                <th style={thStyle2}>Type</th>
                <th style={thStyle2}>Status</th>
                <th style={{ ...thStyle2, textAlign: 'right' }}>Actions</th>
              </tr>
            </thead>
            <tbody>
              {components.map((comp) => (
                <tr key={comp.id} style={{ borderBottom: '1px solid #f3f4f6' }}>
                  <td style={{ padding: '12px 16px' }}>
                    <div style={{ fontWeight: 600, fontSize: 14 }}>{comp.name}</div>
                    <div style={{ fontSize: 12, color: '#6b7280', marginTop: 2 }}>{comp.description}</div>
                    <div style={{ fontSize: 11, color: '#a1a1aa', fontFamily: 'monospace', marginTop: 2 }}>{comp.slug}</div>
                  </td>
                  <td style={{ padding: '12px 16px' }}>
                    <span style={{
                      padding: '2px 8px', borderRadius: 4, fontSize: 11, fontWeight: 600,
                      background: '#f3f4f6', color: '#374151', textTransform: 'capitalize',
                    }}>
                      {comp.category}
                    </span>
                  </td>
                  <td style={{ padding: '12px 16px', fontSize: 12, color: '#6b7280' }}>
                    {comp.is_user_created ? 'Custom' : 'Built-in'}
                  </td>
                  <td style={{ padding: '12px 16px' }}>
                    <button
                      onClick={() => handleToggle(comp)}
                      style={{
                        padding: '4px 12px', borderRadius: 20, border: 'none', fontSize: 12, fontWeight: 600,
                        cursor: 'pointer', transition: 'all 0.15s',
                        background: comp.is_active ? '#dcfce7' : '#f3f4f6',
                        color: comp.is_active ? '#16a34a' : '#9ca3af',
                      }}
                    >
                      {comp.is_active ? 'Active' : 'Inactive'}
                    </button>
                  </td>
                  <td style={{ padding: '12px 16px', textAlign: 'right' }}>
                    <div style={{ display: 'flex', gap: 6, justifyContent: 'flex-end' }}>
                      <button
                        onClick={() => handleEdit(comp)}
                        style={{
                          padding: '5px 14px', background: '#7c3aed', color: '#fff',
                          border: 'none', borderRadius: 6, fontSize: 12,
                          fontWeight: 600, cursor: 'pointer',
                        }}
                      >
                        Edit
                      </button>
                      <button
                        onClick={() => handleDelete(comp)}
                        style={{
                          padding: '5px 12px', background: '#fff', color: '#ef4444',
                          border: '1px solid #fecaca', borderRadius: 6, fontSize: 12,
                          fontWeight: 500, cursor: 'pointer',
                        }}
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Edit Modal */}
      {editingId !== null && (
        <div style={{
          position: 'fixed', top: 0, right: 0, bottom: 0, left: 0,
          background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center',
          zIndex: 100000,
        }} onClick={() => setEditingId(null)}>
          <div style={{ background: '#fff', borderRadius: 12, padding: 24, width: 600, maxWidth: '90%', maxHeight: '90vh', overflowY: 'auto' }} onClick={(e) => e.stopPropagation()}>
            <h3 style={{ margin: '0 0 16px', fontSize: 16, fontWeight: 700 }}>Edit Component</h3>
            <div style={{ marginBottom: 12 }}>
              <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: '#6b7280', marginBottom: 4 }}>Name</label>
              <input type="text" value={editName} onChange={(e) => setEditName(e.target.value)}
                style={{ width: '100%', padding: '8px 12px', border: '1px solid #d1d5db', borderRadius: 6, fontSize: 14, boxSizing: 'border-box' }} />
            </div>
            <div style={{ marginBottom: 12 }}>
              <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: '#6b7280', marginBottom: 4 }}>Description</label>
              <textarea value={editDesc} onChange={(e) => setEditDesc(e.target.value)} rows={3}
                style={{ width: '100%', padding: '8px 12px', border: '1px solid #d1d5db', borderRadius: 6, fontSize: 14, resize: 'vertical', boxSizing: 'border-box' }} />
            </div>
            <div style={{ marginBottom: 12 }}>
              <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: '#6b7280', marginBottom: 4 }}>Category</label>
              <input type="text" value={editCategory} onChange={(e) => setEditCategory(e.target.value)}
                style={{ width: '100%', padding: '8px 12px', border: '1px solid #d1d5db', borderRadius: 6, fontSize: 14, boxSizing: 'border-box' }} />
            </div>
            <div style={{ marginBottom: 16 }}>
              <label style={{ display: 'block', fontSize: 12, fontWeight: 600, color: '#6b7280', marginBottom: 4 }}>Content Schema (JSON)</label>
              <textarea value={editSchema} onChange={(e) => setEditSchema(e.target.value)} rows={10}
                style={{ width: '100%', padding: '8px 12px', border: '1px solid #d1d5db', borderRadius: 6, fontSize: 12, fontFamily: 'monospace', resize: 'vertical', boxSizing: 'border-box' }} />
              <p style={{ fontSize: 11, color: '#9ca3af', margin: '4px 0 0' }}>
                Define editable fields. Example: {'{"fields":[{"key":"text","label":"Text","type":"text","default":"Hello"}]}'}
              </p>
            </div>
            <div style={{ display: 'flex', gap: 8, justifyContent: 'flex-end' }}>
              <button onClick={() => setEditingId(null)}
                style={{ padding: '8px 16px', background: '#f3f4f6', border: 'none', borderRadius: 6, fontSize: 13, cursor: 'pointer', fontWeight: 500 }}>
                Cancel
              </button>
              <button onClick={handleSaveEdit}
                style={{ padding: '8px 16px', background: '#7c3aed', color: '#fff', border: 'none', borderRadius: 6, fontSize: 13, fontWeight: 600, cursor: 'pointer' }}>
                Save Changes
              </button>
            </div>
          </div>
        </div>
      )}
    </AdminLayout>
  );
}
