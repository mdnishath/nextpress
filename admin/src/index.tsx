import { createRoot } from '@wordpress/element';
import App from './App';
import './styles/builder.css';

// Pages where the React SPA takes over rendering.
// All other pages keep the PHP dashboard content inside #npb-admin-root.
const REACT_PAGES = ['nextpress', 'nextpress-pages', 'nextpress-headers', 'nextpress-footers', 'nextpress-components'];

const params = new URLSearchParams(window.location.search);
const currentPage = params.get('page') || '';
const isBuilderMode = params.has('edit');
const shouldMountReact = isBuilderMode || REACT_PAGES.includes(currentPage);

if (shouldMountReact) {
  const root = document.getElementById('npb-admin-root');
  if (root) {
    createRoot(root).render(<App />);
  }
}
