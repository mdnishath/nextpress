Now I have a thorough understanding of the existing codebase. Here is the complete architecture planning document.

---

# NextPress Builder -- Technical Architecture & Planning Document

**Version:** 1.0.0-draft
**Date:** March 31, 2026
**Status:** Pre-Development Planning
**Classification:** Open Source Project Specification

---

## Table of Contents

1. Vision & Mission
2. Plugin Name & Branding
3. High-Level Architecture
4. WordPress Plugin Architecture (PHP OOP)
5. Module System
6. Page Builder Architecture
7. Template System
8. Component Style Variants
9. Form Builder Architecture
10. Theme System
11. REST API Design
12. Next.js Frontend Architecture
13. Security Architecture
14. Performance Strategy
15. Development Roadmap
16. Tech Stack Summary
17. Open Source Strategy
18. File/Directory Structure

---

## 1. Vision & Mission

### What This Is

NextPress Builder is an open-source WordPress plugin paired with a Next.js starter framework that turns WordPress into a full visual page builder for headless Next.js websites. It is specifically designed for agencies and freelancers who build local business websites and need to deliver high-quality, performant sites in under 10 minutes using a drag-and-drop interface inside WordPress.

The plugin replaces the need for Elementor, Yoast, Contact Form 7, WPForms, Schema Pro, Wordfence, and a dozen other plugins by providing all of these capabilities in a single, cohesive system that outputs static or ISR-rendered Next.js pages scoring 95+ on Google PageSpeed.

### Why It Is Unique

The current market has two disconnected worlds:

1. **Traditional WordPress page builders** (Elementor, Divi, Beaver Builder) -- powerful visual editors, but they produce bloated, slow WordPress-rendered HTML. They are not headless-compatible.

2. **Headless WordPress frameworks** (Faust.js, Next.js WordPress Starter, WPGraphQL) -- developer-focused tools that require writing code for every page. No visual builder. No drag-and-drop. Clients cannot edit layouts.

NextPress Builder bridges this gap entirely. The WordPress admin becomes a full visual builder that outputs structured JSON data. The Next.js frontend consumes that data and renders it with modern React components, Tailwind CSS, and all the performance optimizations that come with the Next.js platform.

### Target Audience

- **Primary:** Freelance web developers and small agencies building local business websites (5-50 sites per year)
- **Secondary:** WordPress developers transitioning to headless architecture who want to keep the WordPress editing experience
- **Tertiary:** Local business owners who want to self-manage their website content after initial setup

### Market Gap Analysis

| Capability | Elementor | Faust.js | WPGraphQL + Custom | NextPress Builder |
|---|---|---|---|---|
| Visual drag & drop | Yes | No | No | Yes |
| Next.js output | No | Yes | Yes | Yes |
| PageSpeed 95+ | Rarely | Yes | Yes | Yes |
| Built-in forms | Yes (Pro) | No | No | Yes |
| Built-in SEO | No (needs Yoast) | No | No | Yes |
| Built-in schema | No | No | No | Yes |
| Template library | Yes | No | No | Yes (20 business types) |
| Style variants per component | Limited | N/A | N/A | 20 per component |
| No-code for clients | Yes | No | No | Yes |
| Open source | Partially | Yes | Yes | Yes |
| All-in-one (no other plugins needed) | No | No | No | Yes |

---

## 2. Plugin Name & Branding

### Name Candidates

1. **NextPress Builder** -- Clear, descriptive, immediately communicates the Next.js + WordPress combination. Strong SEO potential for "nextjs wordpress builder" searches.

2. **HeadlessKit** -- Short, memorable, developer-friendly. Positions the product as a toolkit. Risk: slightly generic.

3. **LaunchPress** -- Action-oriented, implies speed ("launch a site fast"). Good for the "build in 10 minutes" narrative. Risk: no Next.js reference in name.

4. **SiteForge** -- Strong, evocative of craftsmanship and building. Framework-agnostic naming allows future expansion beyond Next.js. Risk: no WordPress reference.

5. **NextForge** -- Combines Next.js reference with the craftsmanship metaphor. Short, memorable. Risk: could be confused with existing "next" npm packages.

### Recommended: **NextPress Builder**

Rationale: For an open-source project, discoverability matters more than cleverness. "NextPress" immediately tells developers what the tool connects (Next.js + WordPress), and "Builder" tells non-developers what it does. It will rank well for organic search queries.

### Tagline Candidates

- "Build local business websites in minutes, not months."
- "The WordPress page builder that outputs Next.js."
- "Elementor power. Next.js performance. Zero compromise."
- "Drag, drop, deploy. WordPress to Next.js in 10 minutes."

---

## 3. High-Level Architecture

### System Overview

```
+------------------------------------------------------------------+
|                        BROWSER (End User)                        |
|  Next.js App (Static/ISR) -- Tailwind CSS + React Components    |
+-------------------------------+----------------------------------+
                                |
                    HTTPS (CDN / Vercel / etc.)
                                |
+-------------------------------+----------------------------------+
|                     NEXT.JS SERVER (Node.js)                     |
|  App Router | ISR | API Routes | Revalidation Endpoint          |
|  Component Registry | Dynamic Renderer | Image Optimization     |
+-------------------------------+----------------------------------+
                                |
                   REST API (HTTPS + JWT/App Passwords)
                                |
+-------------------------------+----------------------------------+
|                   WORDPRESS SERVER (PHP 8.1+)                    |
|                                                                  |
|  +------------------+  +------------------+  +----------------+  |
|  | NextPress Plugin |  |  Custom REST API |  | React Admin UI |  |
|  |  (PHP OOP Core)  |  |   /npb/v1/*      |  | (wp-admin SPA) |  |
|  +------------------+  +------------------+  +----------------+  |
|                                                                  |
|  +------------------+  +------------------+  +----------------+  |
|  |  Module Loader   |  | Page Builder     |  | Theme Manager  |  |
|  |  (Auto-discovery)|  | (Drag & Drop)    |  | (CSS Vars)     |  |
|  +------------------+  +------------------+  +----------------+  |
|                                                                  |
|  +------------------+  +------------------+  +----------------+  |
|  | Form Builder     |  | SEO Manager      |  | Schema Manager |  |
|  +------------------+  +------------------+  +----------------+  |
|                                                                  |
|  MySQL Database (wp_* tables + npb_* custom tables)              |
+------------------------------------------------------------------+
```

### Data Flow: WP Admin to Browser

```
1. Admin edits page in WordPress
   |
   v
2. React-based admin UI (loaded in wp-admin via @wordpress/scripts)
   |
   v
3. Admin UI calls WP REST API to save structured JSON data
   |  POST /wp-json/npb/v1/pages/{id}/sections
   |  Body: { sections: [...], meta: {...}, seo: {...} }
   v
4. Data stored in npb_pages + npb_sections custom tables
   |
   v
5. WordPress fires webhook to Next.js revalidation endpoint
   |  POST https://frontend.com/api/revalidate
   |  Body: { secret: "...", paths: ["/", "/services/plumbing"] }
   v
6. Next.js revalidates affected pages (on-demand ISR)
   |
   v
7. Next page request hits Next.js server
   |  - Fetches structured JSON from WP REST API
   |  - Maps section types to React components via Component Registry
   |  - Applies theme CSS variables from WP theme data
   |  - Renders full HTML with Tailwind + Framer Motion
   v
8. Browser receives fully rendered HTML + hydrated React app
   |  - PageSpeed 95+
   |  - Schema/JSON-LD embedded
   |  - Security headers applied via middleware
```

### REST API Design Between WP and Next.js

The API namespace is `/wp-json/npb/v1/`. All endpoints return JSON. The API is read-only for the Next.js frontend (authentication required only for write operations from the admin UI). Caching headers are set per endpoint.

The API is designed around resources, not pages. The Next.js frontend assembles pages from multiple resource calls (site config, page sections, theme, navigation, etc.), which allows aggressive caching of stable resources while only revalidating what changes.

---

## 4. WordPress Plugin Architecture (PHP OOP)

### Design Principles

- **PHP 8.1+ minimum** -- use typed properties, enums, named arguments, readonly properties, union types, match expressions
- **PSR-4 autoloading** via Composer
- **Single Responsibility** -- each class does one thing
- **Dependency Injection** -- core services are injected, not globally accessed
- **WordPress-native where possible** -- use WP hooks, WP REST API, WP capabilities system, WP object cache
- **No global functions** -- everything lives in namespaced classes
- **No direct database queries in modules** -- all DB access goes through Repository classes

### Core Classes and Responsibilities

```
NextPressBuilder\Plugin (main entry point)
  - Bootstraps the application
  - Registers activation/deactivation hooks
  - Initializes the Container (DI)
  - Loads the ModuleManager

NextPressBuilder\Core\Container
  - Simple dependency injection container
  - Registers and resolves service classes
  - Singleton management

NextPressBuilder\Core\ModuleManager
  - Discovers and loads modules from /modules directory
  - Validates modules implement ModuleInterface
  - Manages module dependencies and load order
  - Provides module registry (get module by slug)

NextPressBuilder\Core\ModuleInterface
  - slug(): string
  - name(): string
  - version(): string
  - dependencies(): array
  - register(Container $container): void
  - boot(): void

NextPressBuilder\Core\RestApiManager
  - Registers all REST API routes
  - Handles API versioning (v1, v2, etc.)
  - Applies rate limiting middleware
  - Handles authentication verification

NextPressBuilder\Core\DatabaseManager
  - Runs migrations on activation/upgrade
  - Creates/updates custom tables
  - Provides schema version tracking

NextPressBuilder\Core\AssetManager
  - Enqueues admin CSS/JS (React admin panels)
  - Handles script localization (wp_localize_script)
  - Manages build artifacts from @wordpress/scripts

NextPressBuilder\Core\HookManager
  - Centralized hook registration
  - Provides typed filter/action helpers
  - Module-scoped hook namespacing

NextPressBuilder\Core\SettingsManager
  - Manages plugin-wide settings
  - Settings stored in wp_options with npb_ prefix
  - Provides typed getter/setter methods
  - Handles settings migration between versions

NextPressBuilder\Core\WebhookManager
  - Manages outgoing webhooks (revalidation, etc.)
  - Queue-based delivery with retry logic
  - Logs webhook delivery status

NextPressBuilder\Core\Repository\AbstractRepository
  - Base class for all data repositories
  - Provides CRUD operations on custom tables
  - Handles sanitization and validation
  - Supports pagination, filtering, ordering

NextPressBuilder\Core\Sanitizer
  - Centralized input sanitization
  - Type-aware sanitization (HTML, URL, color, JSON, etc.)
  - Validates against defined schemas

NextPressBuilder\Core\Capability
  - Defines custom capabilities (npb_edit_pages, npb_manage_themes, etc.)
  - Maps capabilities to WP roles on activation
  - Provides capability check helpers
```

### Database Schema (Custom Tables)

All custom tables use the `{wp_prefix}npb_` prefix.

**npb_pages**
```sql
CREATE TABLE {prefix}npb_pages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  wp_post_id BIGINT UNSIGNED NULL,          -- linked WP post/page (nullable for standalone)
  slug VARCHAR(255) NOT NULL,
  title VARCHAR(255) NOT NULL,
  status ENUM('draft','published','archived') DEFAULT 'draft',
  page_type ENUM('page','header','footer','component') DEFAULT 'page',
  header_id BIGINT UNSIGNED NULL,           -- assigned header (FK to npb_pages where page_type='header')
  footer_id BIGINT UNSIGNED NULL,           -- assigned footer (FK to npb_pages where page_type='footer')
  seo_title VARCHAR(255) NULL,
  seo_description TEXT NULL,
  seo_keywords TEXT NULL,
  og_image VARCHAR(500) NULL,
  schema_type VARCHAR(100) NULL,            -- LocalBusiness, Restaurant, etc.
  schema_data JSON NULL,                     -- custom schema overrides
  template_id VARCHAR(100) NULL,            -- source template slug
  settings JSON NULL,                        -- page-level settings (layout width, etc.)
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_slug (slug),
  INDEX idx_status (status),
  INDEX idx_page_type (page_type),
  INDEX idx_wp_post_id (wp_post_id)
);
```

**npb_sections**
```sql
CREATE TABLE {prefix}npb_sections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_id BIGINT UNSIGNED NOT NULL,         -- FK to npb_pages
  section_type VARCHAR(100) NOT NULL,       -- hero, services_grid, cta_banner, etc.
  variant_id VARCHAR(100) DEFAULT 'default', -- which style variant (variant-01, variant-02, etc.)
  sort_order INT UNSIGNED DEFAULT 0,
  enabled TINYINT(1) DEFAULT 1,
  content JSON NOT NULL,                     -- section-specific content data
  style JSON NULL,                           -- style overrides (colors, spacing, etc.)
  responsive JSON NULL,                      -- per-breakpoint overrides
  visibility JSON NULL,                      -- { desktop: true, tablet: true, mobile: false }
  animation VARCHAR(50) NULL,               -- fadeIn, slideUp, etc.
  custom_css TEXT NULL,
  custom_id VARCHAR(100) NULL,              -- for anchor links
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_page_id (page_id),
  INDEX idx_sort_order (page_id, sort_order),
  FOREIGN KEY (page_id) REFERENCES {prefix}npb_pages(id) ON DELETE CASCADE
);
```

**npb_components**
```sql
CREATE TABLE {prefix}npb_components (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  category VARCHAR(100) NOT NULL,           -- hero, features, testimonials, cta, etc.
  description TEXT NULL,
  is_user_created TINYINT(1) DEFAULT 0,
  content_schema JSON NOT NULL,             -- defines what fields this component has
  default_content JSON NULL,                -- default values
  default_style JSON NULL,                  -- default styling
  preview_image VARCHAR(500) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_category (category)
);
```

**npb_style_variants**
```sql
CREATE TABLE {prefix}npb_style_variants (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  component_slug VARCHAR(100) NOT NULL,     -- FK to npb_components.slug
  variant_slug VARCHAR(100) NOT NULL,       -- variant-01, variant-02, etc.
  name VARCHAR(255) NOT NULL,               -- "Centered with overlay", "Split layout", etc.
  style JSON NOT NULL,                       -- the variant's style data
  preview_image VARCHAR(500) NULL,
  is_premium TINYINT(1) DEFAULT 0,
  sort_order INT UNSIGNED DEFAULT 0,
  UNIQUE KEY idx_component_variant (component_slug, variant_slug),
  INDEX idx_component (component_slug)
);
```

**npb_forms**
```sql
CREATE TABLE {prefix}npb_forms (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  fields JSON NOT NULL,                      -- array of field definitions
  settings JSON NOT NULL,                    -- notifications, redirects, spam protection, etc.
  conditional_logic JSON NULL,              -- conditional visibility rules
  multi_step JSON NULL,                     -- step definitions
  styling JSON NULL,                        -- form-level styling
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**npb_form_submissions**
```sql
CREATE TABLE {prefix}npb_form_submissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  form_id BIGINT UNSIGNED NOT NULL,
  data JSON NOT NULL,                        -- submitted field values
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  referrer VARCHAR(500) NULL,
  status ENUM('unread','read','starred','archived','spam') DEFAULT 'unread',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_form_id (form_id),
  INDEX idx_status (status),
  INDEX idx_created_at (created_at),
  FOREIGN KEY (form_id) REFERENCES {prefix}npb_forms(id) ON DELETE CASCADE
);
```

**npb_themes**
```sql
CREATE TABLE {prefix}npb_themes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  is_active TINYINT(1) DEFAULT 0,
  colors JSON NOT NULL,                      -- full color palette
  typography JSON NOT NULL,                  -- font families, sizes, weights
  spacing JSON NOT NULL,                     -- spacing scale
  buttons JSON NOT NULL,                     -- button style presets
  borders JSON NULL,                        -- border radius, widths
  shadows JSON NULL,                        -- shadow presets
  dark_mode JSON NULL,                      -- dark mode color overrides
  custom_css TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**npb_buttons**
```sql
CREATE TABLE {prefix}npb_buttons (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  preset JSON NOT NULL,                      -- { bg, color, hoverBg, hoverColor, radius, padding, fontSize, fontWeight, shadow, border, animation }
  is_default TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**npb_navigation_menus**
```sql
CREATE TABLE {prefix}npb_navigation_menus (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  location ENUM('header','footer','sidebar','custom') DEFAULT 'header',
  items JSON NOT NULL,                       -- nested menu items array
  settings JSON NULL,                       -- menu-level settings
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**npb_templates**
```sql
CREATE TABLE {prefix}npb_templates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  business_type VARCHAR(100) NOT NULL,      -- restaurant, plumber, lawyer, etc.
  description TEXT NULL,
  preview_image VARCHAR(500) NULL,
  data JSON NOT NULL,                        -- full template data (pages, sections, theme, forms, navigation)
  version VARCHAR(20) DEFAULT '1.0.0',
  is_premium TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_business_type (business_type)
);
```

### Admin UI Architecture

The WordPress admin UI is built with React using `@wordpress/scripts` for the build pipeline. This provides seamless integration with the WordPress admin environment while enabling a modern SPA experience for the page builder and settings panels.

**Admin Pages (top-level menu: "NextPress"):**

- **Dashboard** -- overview, quick stats, links to all modules
- **Pages** -- list of all NextPress pages, create/edit with the builder
- **Headers** -- manage header layouts
- **Footers** -- manage footer layouts
- **Forms** -- form builder, submission viewer
- **Components** -- component library, user-created components
- **Templates** -- template browser, import flow
- **Theme** -- color palette, typography, spacing, button presets
- **SEO** -- global SEO settings, per-page SEO, sitemap config
- **Navigation** -- menu builder
- **Settings** -- API config, Next.js URL, revalidation settings, security

Each admin page is a React SPA mounted via `wp_enqueue_script` with `@wordpress/element`. The React app communicates with the plugin's REST API endpoints for all CRUD operations.

The page builder specifically uses a drag-and-drop library (dnd-kit via `@dnd-kit/core` and `@dnd-kit/sortable`) for section reordering, with a component panel on the left, a live preview iframe in the center, and a style/settings panel on the right.

---

## 5. Module System

### How Modules Are Registered and Loaded

Modules follow an auto-discovery pattern. The `ModuleManager` scans the `modules/` directory for subdirectories containing a class that implements `ModuleInterface`. Each module is a self-contained unit with its own:

- PHP classes (repositories, controllers, REST endpoints)
- Admin React components (if needed)
- REST API routes
- Database migrations
- Default data/fixtures

### Module Interface

```php
namespace NextPressBuilder\Core;

interface ModuleInterface
{
    /** Unique machine-readable identifier */
    public function slug(): string;

    /** Human-readable name */
    public function name(): string;

    /** Semantic version */
    public function version(): string;

    /** Array of module slugs this module depends on */
    public function dependencies(): array;

    /** Register services, repositories, hooks with the container */
    public function register(Container $container): void;

    /** Called after all modules are registered. Safe to use other module services. */
    public function boot(): void;

    /** Return REST API route definitions for this module */
    public function routes(): array;

    /** Return database migration definitions */
    public function migrations(): array;
}
```

### Module Load Order

1. `ModuleManager::discover()` -- scans `/modules/*/Module.php` files
2. `ModuleManager::resolve()` -- topological sort based on `dependencies()`
3. `ModuleManager::register()` -- calls `register()` on each module in order
4. `ModuleManager::boot()` -- calls `boot()` on each module in order
5. `ModuleManager::registerRoutes()` -- registers all REST routes

### Planned Modules

#### 5.1 Page Builder Module (`page-builder`)
**Dependencies:** `theme-manager`, `component-library`

The core drag-and-drop page building experience. Manages page data, section ordering, section content editing, responsive editing, and live preview. This module is the heart of the plugin.

Responsibilities:
- Page CRUD (create, read, update, delete, duplicate, archive)
- Section management (add, remove, reorder, enable/disable)
- Section content editing (per-field editors based on content schema)
- Section style editing (colors, spacing, typography overrides)
- Responsive editing (mobile/tablet/desktop breakpoint toggles)
- Live preview via iframe pointing to a special Next.js preview route
- Page-level settings (layout width, custom CSS, custom JS)
- Import/export of individual pages as JSON
- Undo/redo history (stored in-memory in the React admin)
- Keyboard shortcuts (Ctrl+Z undo, Ctrl+Shift+Z redo, Ctrl+S save, Ctrl+D duplicate section)

#### 5.2 Header Builder Module (`header-builder`)
**Dependencies:** `page-builder`, `navigation-manager`, `button-manager`

Manages multiple header layouts that can be assigned per-page.

Responsibilities:
- Header CRUD (create multiple headers, each is a special page_type='header' in npb_pages)
- Header section types: logo, navigation, CTA button, top bar, search, social icons, contact info
- Layout options: standard, centered, split nav, hamburger-only, transparent overlay
- Sticky behavior configuration
- Mobile menu configuration (hamburger, slide-out, full-screen overlay)
- Header assignment to pages (per-page override or global default)
- Scrolled-state styling (background change, shrink, shadow)

#### 5.3 Footer Builder Module (`footer-builder`)
**Dependencies:** `page-builder`, `navigation-manager`

Manages multiple footer layouts assignable per-page.

Responsibilities:
- Footer CRUD (multiple footers, page_type='footer')
- Footer section types: logo + description, navigation columns, contact info, social links, newsletter form, copyright bar, map embed
- Column layout configuration (2, 3, 4, or 5 columns)
- Widget areas within footer columns
- Per-page footer assignment with global default fallback

#### 5.4 Form Builder Module (`form-builder`)
**Dependencies:** none

A full form building system with advanced capabilities.

Responsibilities:
- Form CRUD with visual field editor
- Field types: text, email, phone, textarea, select, radio, checkbox, file upload, date, time, number, hidden, HTML block, divider
- Multi-step form support (step definitions with progress indicator)
- Conditional logic (show/hide fields based on other field values)
- Field validation rules (required, min/max length, pattern, custom regex)
- File upload handling (type restrictions, size limits, virus scan integration point)
- Spam protection: honeypot (built-in), reCAPTCHA v3 (optional), Cloudflare Turnstile (optional), rate limiting (IP-based)
- Email notification system (admin notification, user confirmation, custom recipients)
- Form submission storage in npb_form_submissions
- Submission viewer in admin (filter, search, export CSV, mark as read/starred/spam)
- Integration hooks for third-party services (Mailchimp, ConvertKit, Zapier webhook)
- Form embedding in any section via form picker

#### 5.5 SEO Manager Module (`seo-manager`)
**Dependencies:** none

Complete SEO management eliminating the need for Yoast or RankMath.

Responsibilities:
- Per-page SEO: title, description, keywords, canonical URL, robots directives
- Open Graph meta (title, description, image, type)
- Twitter Card meta
- Global SEO defaults (title template, default description, site-wide robots)
- XML sitemap generation (output as JSON for Next.js to render)
- Robots.txt management
- Breadcrumb schema generation
- SEO analysis/score per page (keyword density, title length, description length, heading structure)
- Redirect manager (301/302 redirects stored in DB, served as JSON for Next.js middleware)
- Social preview (how the page looks when shared on Facebook/Twitter/LinkedIn)

#### 5.6 Schema/JSON-LD Manager Module (`schema-manager`)
**Dependencies:** `seo-manager`

Automatic and manual structured data management.

Responsibilities:
- Auto-generate schema based on business type and page content
- Supported schema types: LocalBusiness, Restaurant, LegalService, Dentist, Plumber, Electrician, HomeRepair, MedicalBusiness, FinancialService, RealEstateAgent, AutomotiveBusiness, BeautySalon, FitnessCenter, EducationalOrganization, plus generic Organization and WebPage
- Per-page schema overrides
- FAQ schema auto-generation from FAQ sections
- Service schema from service pages
- Review/AggregateRating schema from testimonials
- BreadcrumbList schema from page hierarchy
- HowTo schema for service process sections
- OpeningHoursSpecification from business hours config
- AreaServed from zone configuration
- Schema validation against Google's requirements (flag errors in admin)
- JSON-LD output as structured data consumed by the Next.js frontend

#### 5.7 Theme/Style Manager Module (`theme-manager`)
**Dependencies:** none

Full theming system with presets, custom themes, and design tokens.

Responsibilities:
- Theme CRUD (create, edit, duplicate, export, import)
- Color palette management (primary, secondary, accent, dark, light, success, warning, error + custom colors)
- Typography system (heading font, body font, mono font via Google Fonts, with size scale and weight options)
- Spacing scale (base unit multiplier system: 4px base, so spacing-1=4px, spacing-2=8px, etc.)
- Border radius presets (none, sm, md, lg, xl, full)
- Shadow presets (none, sm, md, lg, xl)
- Button style presets (managed via Button Manager but theme provides defaults)
- Dark mode color mapping (automatic inversion with manual overrides)
- CSS variable output (all tokens output as CSS custom properties for the Next.js frontend)
- Theme switching (one-click switch, all pages update)
- 10+ pre-built themes (Modern, Classic, Bold, Minimal, Warm, Cool, Corporate, Creative, Elegant, Playful)

#### 5.8 Media Manager Module (`media-manager`)
**Dependencies:** none

Enhanced media handling beyond WordPress defaults.

Responsibilities:
- Integration with WordPress Media Library (not replacing it)
- Image optimization metadata (WebP/AVIF conversion flags for Next.js)
- Focal point selection (stored as metadata, passed to Next.js for object-position)
- Alt text enforcement (flag images missing alt text)
- Image size presets for the builder (hero, card, thumbnail, avatar, icon, full-width)
- Lazy loading configuration
- External image URL support (Unsplash, Pexels integration for stock photos)
- SVG upload support with sanitization

#### 5.9 Navigation Manager Module (`navigation-manager`)
**Dependencies:** none

Menu and navigation management separate from WordPress native menus.

Responsibilities:
- Navigation menu CRUD
- Nested menu items (drag-and-drop reordering)
- Menu item types: page link, custom URL, section anchor, phone link, email link, dropdown group
- Menu item attributes: label, URL, icon, badge, target (_blank), nofollow, CSS class
- Multiple menu locations (header primary, header secondary, footer, mobile, sidebar)
- Mega menu support (columns with custom content per dropdown)
- Menu assignment per header/footer

#### 5.10 Button/CTA Manager Module (`button-manager`)
**Dependencies:** `theme-manager`

Reusable button style system.

Responsibilities:
- Button preset CRUD (create named button styles)
- Button properties: background, text color, hover background, hover text color, border, border radius, padding, font size, font weight, font family, text transform, letter spacing, shadow, transition, icon position
- 10+ pre-built button presets (Primary Solid, Secondary Solid, Outline, Ghost, Pill, Sharp, Gradient, Elevated, Minimal, Icon-only)
- Dynamic button component in sections (select preset, override individual properties, set href/action)
- Button group support (multiple buttons in a row with gap control)
- CTA section types that use the button system

#### 5.11 Component Library Module (`component-library`)
**Dependencies:** `theme-manager`, `button-manager`

Manages all available section components and their style variants.

Responsibilities:
- Component registry (all built-in section types)
- Component metadata (name, description, category, content schema, default content, preview image)
- Style variant management (20 variants per component)
- User-created components (save a configured section as a reusable component)
- Component categories: Hero, Features/Services, Testimonials, CTA/Banners, Pricing, Team, FAQ, Gallery, Stats/Counters, Contact/Map, Timeline, Portfolio/Projects, Blog/News, Newsletter, About/Values, Zone/Area, Before/After, Rich Text, Divider/Spacer, Embed
- Component search and filtering in the builder panel

#### 5.12 Template Library Module (`template-library`)
**Dependencies:** all other modules (loaded last)

Pre-designed complete website templates for rapid deployment.

Responsibilities:
- Template browser with previews
- One-click import (creates all pages, sections, header, footer, forms, navigation, theme, SEO defaults)
- Template data includes: all pages with sections, header configuration, footer configuration, navigation menus, form definitions, theme settings, SEO defaults, sample content
- Template customization wizard (after import: change business name, phone, address, colors, logo)
- Import conflict resolution (merge vs. replace)
- Template export (export current site as a template for reuse)
- Community template repository (future: download templates from a central registry)

#### 5.13 Security Manager Module (`security-manager`)
**Dependencies:** none

Security hardening for both WordPress and the API.

Responsibilities:
- Rate limiting on all REST API endpoints (configurable per-endpoint)
- Brute force protection on form submissions (IP-based throttling)
- Input sanitization enforcement (all inputs pass through the Sanitizer class)
- File upload validation (type, size, content inspection)
- CORS configuration for the Next.js frontend
- API key management (generate/revoke keys for the Next.js frontend)
- Audit log (track admin actions: page edits, theme changes, settings updates)
- WordPress hardening recommendations (admin notices for common issues)
- Content Security Policy header suggestions for the Next.js frontend

#### 5.14 Performance Manager Module (`performance-manager`)
**Dependencies:** none

Optimizations for both the WordPress API and the Next.js output.

Responsibilities:
- API response caching (WordPress object cache integration, configurable TTL per endpoint)
- API response compression (gzip)
- Selective field loading (fields parameter on API endpoints to reduce payload size)
- Image optimization recommendations (flag oversized images)
- Critical CSS identification (mark above-the-fold sections)
- Lazy loading configuration per section
- Preload hints generation (fonts, critical images)
- Bundle analysis data (which components are used per page, for Next.js code splitting)
- Cache busting (version parameter on API responses)

#### 5.15 Analytics Integration Module (`analytics-integration`)
**Dependencies:** none

Lightweight analytics integration without third-party plugin dependencies.

Responsibilities:
- Google Analytics 4 integration (measurement ID configuration)
- Google Tag Manager integration
- Facebook Pixel integration
- Custom script injection (head/body, with page-level control)
- Event tracking configuration (button clicks, form submissions, scroll depth)
- Cookie consent integration point (outputs consent config for Next.js to implement)
- Analytics data passed as JSON to Next.js (Next.js handles actual script loading for performance)

#### 5.16 Revalidation/Cache Manager Module (`revalidation-manager`)
**Dependencies:** none

Manages the connection between WordPress content changes and Next.js cache invalidation.

Responsibilities:
- Webhook configuration (Next.js revalidation URL, secret key)
- Automatic revalidation on content changes (uses WordPress hooks: save_post, delete_post, updated_option, acf/save_post, etc.)
- Smart path detection (determine which frontend paths are affected by a content change)
- Manual revalidation trigger (button in admin to revalidate specific paths or all paths)
- Revalidation log (track webhook delivery success/failure with timestamps)
- Bulk revalidation (revalidate all pages after theme change or global setting change)
- Revalidation queue with retry logic (exponential backoff on failure)
- Health check endpoint (Next.js can ping WP to verify connectivity)

---

## 6. Page Builder Architecture

### Section Types and Their Data Models

Every section has three data layers:

1. **Content** -- the actual text, images, and data (stored in `npb_sections.content` as JSON)
2. **Style** -- visual overrides: colors, spacing, typography, backgrounds (stored in `npb_sections.style` as JSON)
3. **Variant** -- which pre-designed layout/style variant to use (stored in `npb_sections.variant_id`)

Example content schema for a Hero section:

```json
{
  "type": "hero",
  "variant_id": "variant-03",
  "content": {
    "badge": "Expert Plumber in Austin",
    "heading": "Your Trusted Local Plumber",
    "subtitle": "Fast & Reliable Service",
    "description": "24/7 emergency plumbing services...",
    "badges": ["Licensed & Insured", "24/7 Emergency", "15+ Years"],
    "image": "https://wp.example.com/wp-content/uploads/hero.jpg",
    "cta_primary_text": "Get Free Quote",
    "cta_primary_link": "/contact",
    "cta_secondary_text": "Call Now",
    "cta_secondary_link": "tel:+15551234567",
    "form_id": "hero_quote",
    "show_form": true
  },
  "style": {
    "bgOverlayColor": "#000000",
    "bgOverlayOpacity": 60,
    "titleColor": "#FFFFFF",
    "titleSize": { "mobile": "36", "tablet": "48", "desktop": "60" },
    "paddingTop": { "mobile": "80", "tablet": "100", "desktop": "120" },
    "paddingBottom": { "mobile": "80", "tablet": "100", "desktop": "120" }
  }
}
```

### Drag & Drop Implementation

The admin builder UI uses `@dnd-kit/core` and `@dnd-kit/sortable` for drag-and-drop. The builder has three panels:

**Left Panel -- Component Palette:**
- Categorized list of all available section components
- Search/filter
- Drag a component from here to the canvas to add it
- Component variants shown as thumbnails within each component type

**Center Panel -- Canvas:**
- Ordered list of sections representing the page
- Each section is a sortable item (drag handle to reorder)
- Clicking a section selects it for editing in the right panel
- Sections can be collapsed/expanded
- Visual indicators for enabled/disabled sections
- "Add section" button between sections and at the bottom

**Right Panel -- Editor:**
- When a section is selected: shows content fields and style controls
- Content tab: form fields based on the component's content schema
- Style tab: color pickers, spacing sliders, typography selectors, responsive breakpoint toggles
- Variant tab: thumbnail grid of all 20 style variants, click to switch
- Advanced tab: custom CSS, custom ID, animation, visibility per breakpoint

### Style System

The style system operates at four levels (highest priority wins):

1. **Theme defaults** -- global colors, typography, spacing from the active theme
2. **Component defaults** -- built-in sensible defaults per component type
3. **Variant styles** -- the selected variant's predefined styling
4. **Section overrides** -- per-instance style overrides set in the builder

Responsive breakpoints:
- Mobile: 0-767px
- Tablet: 768-1023px
- Desktop: 1024px+

All spacing and font size values support per-breakpoint configuration via the `ResponsiveValue` pattern already established in the existing codebase (`{ mobile: string, tablet: string, desktop: string }`).

### Component Tree Structure

```
Page
  |-- Header (separate entity, assigned via header_id)
  |-- Section[]  (ordered array, each with type + variant + content + style)
  |     |-- Hero (variant-03)
  |     |-- ServicesGrid (variant-07)
  |     |-- Testimonials (variant-12)
  |     |-- CTABanner (variant-02)
  |     |-- ContactForm (variant-05)
  |     |-- Map (variant-01)
  |-- Footer (separate entity, assigned via footer_id)
```

### Live Preview Mechanism

The builder embeds an iframe pointing to a special Next.js preview route:

```
https://frontend.com/api/preview?secret=PREVIEW_SECRET&pageId=123
```

This route:
1. Accepts the page data as a POST body (or fetches it from the WP API using the pageId)
2. Renders the page using the same component registry as production
3. Returns the rendered HTML in the iframe
4. The iframe refreshes on save (debounced, 500ms after last change)

For near-instant preview, the admin can also use a "hot preview" mode where changes are sent via `postMessage` to the iframe, and the Next.js preview client updates the DOM without a full page reload. This requires a small client-side script in the preview page.

### Import/Export System

Pages can be exported as JSON files containing all section data, style overrides, and referenced assets (as URLs). Import resolves asset URLs and creates new npb_pages/npb_sections records. This enables:

- Sharing page layouts between sites
- Backup/restore of individual pages
- Template creation from existing pages

---

## 7. Template System

### 20 Local Business Templates

Each template includes a complete website: 5-7 pages, header, footer, navigation, forms, theme, and sample content.

| # | Template Slug | Business Type | Key Pages | Schema Type |
|---|---|---|---|---|
| 1 | `restaurant` | Restaurant / Cafe | Home, Menu, About, Reservations, Contact, Gallery | Restaurant |
| 2 | `plumber` | Plumbing Service | Home, Services, About, Emergency, Contact, Service Areas | Plumber |
| 3 | `lawyer` | Law Firm / Attorney | Home, Practice Areas, About, Case Results, Contact, Blog | LegalService |
| 4 | `dentist` | Dental Practice | Home, Services, About, Patient Info, Contact, Team | Dentist |
| 5 | `electrician` | Electrical Service | Home, Services, About, Emergency, Contact, Reviews | Electrician |
| 6 | `hvac` | Heating & Cooling | Home, Services, About, Maintenance Plans, Contact, Service Areas | HomeRepair |
| 7 | `roofing` | Roofing Contractor | Home, Services, About, Projects, Contact, Quote | RoofingContractor |
| 8 | `landscaping` | Landscaping | Home, Services, About, Gallery, Contact, Quote | LandscapingBusiness |
| 9 | `auto-repair` | Auto Mechanic | Home, Services, About, Appointment, Contact, Reviews | AutomotiveBusiness |
| 10 | `salon` | Hair Salon / Spa | Home, Services, About, Team, Booking, Gallery | BeautySalon |
| 11 | `fitness` | Gym / Personal Trainer | Home, Programs, About, Schedule, Contact, Pricing | FitnessCenter |
| 12 | `real-estate` | Real Estate Agent | Home, Listings, About, Testimonials, Contact, Blog | RealEstateAgent |
| 13 | `accountant` | CPA / Accounting | Home, Services, About, Resources, Contact, Blog | FinancialService |
| 14 | `veterinarian` | Vet Clinic | Home, Services, About, Team, Emergency, Contact | VeterinaryCare |
| 15 | `cleaning` | Cleaning Service | Home, Services, About, Pricing, Contact, Quote | HomeRepair |
| 16 | `photographer` | Photography Studio | Home, Portfolio, About, Packages, Contact, Blog | LocalBusiness |
| 17 | `moving` | Moving Company | Home, Services, About, Quote Calculator, Contact, Areas | MovingCompany |
| 18 | `chiropractor` | Chiropractic Clinic | Home, Services, About, Patient Info, Contact, Team | MedicalBusiness |
| 19 | `daycare` | Child Care Center | Home, Programs, About, Enrollment, Contact, Gallery | EducationalOrganization |
| 20 | `construction` | General Contractor | Home, Services, About, Projects, Contact, Quote | GeneralContractor |

### Template Data Structure

```json
{
  "slug": "plumber",
  "name": "Professional Plumber",
  "business_type": "plumber",
  "version": "1.0.0",
  "description": "Complete website for plumbing services. Includes emergency service page, service area map, and quick quote form.",
  "preview_image": "/templates/plumber/preview.jpg",
  "data": {
    "theme": {
      "slug": "plumber-default",
      "colors": { "primary": "#1E56A0", "accent": "#F6B93B", ... },
      "typography": { "heading": "Montserrat", "body": "Open Sans", ... },
      "spacing": { "base": 4 },
      "buttons": { ... }
    },
    "navigation": [
      { "slug": "main", "location": "header", "items": [...] },
      { "slug": "footer", "location": "footer", "items": [...] }
    ],
    "header": {
      "sections": [...],
      "settings": { "sticky": true, "transparent": false, ... }
    },
    "footer": {
      "sections": [...],
      "settings": { "columns": 4, ... }
    },
    "pages": [
      {
        "slug": "home",
        "title": "Home",
        "seo": { "title": "{business_name} | Professional Plumbing Services in {city}", ... },
        "schema_type": "Plumber",
        "sections": [
          { "type": "hero", "variant_id": "variant-05", "content": { ... }, "style": { ... } },
          { "type": "services_grid", "variant_id": "variant-02", "content": { ... } },
          { "type": "stats_counter", "variant_id": "variant-01", "content": { ... } },
          { "type": "testimonials", "variant_id": "variant-08", "content": { ... } },
          { "type": "cta_banner", "variant_id": "variant-03", "content": { ... } },
          { "type": "zone_intervention", "variant_id": "variant-01", "content": { ... } }
        ]
      },
      ...
    ],
    "forms": [
      { "slug": "contact", "name": "Contact Form", "fields": [...], "settings": { ... } },
      { "slug": "quick_quote", "name": "Quick Quote", "fields": [...], "settings": { ... } }
    ],
    "sample_services": [
      { "title": "Emergency Plumbing", "slug": "emergency-plumbing", ... },
      { "title": "Drain Cleaning", "slug": "drain-cleaning", ... },
      ...
    ]
  }
}
```

### One-Click Import Flow

1. User browses template library in admin
2. User clicks "Preview" to see live preview of template
3. User clicks "Import This Template"
4. Wizard opens:
   - Step 1: Enter business details (name, phone, email, address)
   - Step 2: Choose color scheme (template default or pick custom)
   - Step 3: Upload logo (optional, can do later)
   - Step 4: Confirm import (show summary of what will be created)
5. Import runs:
   - Creates theme entry
   - Creates navigation menus
   - Creates header page
   - Creates footer page
   - Creates all content pages with sections
   - Creates forms
   - Applies business details to template variables (`{business_name}`, `{phone}`, `{city}`, etc.)
   - Triggers full revalidation of Next.js frontend
6. Wizard shows success with links to edit each page

### Template Customization Workflow

After import, users can:
- Edit any page in the builder (change sections, content, styles, variants)
- Switch the theme or customize colors/fonts
- Swap sections between variant styles (click variant thumbnail in builder)
- Add/remove sections
- Create new pages using the builder
- Save customized pages as user templates for reuse

---

## 8. Component Style Variants

### How 20 Pre-Designed Styles Per Component Work

Each component (section type) has a base React component on the Next.js side that accepts a `variant` prop. The variant determines the layout structure, visual treatment, and default styling while the content stays the same.

For example, a Hero component with `variant-01` might be a full-screen background image with centered text, while `variant-12` might be a split layout with text on the left and an image on the right.

The variant system works like this:

1. **WordPress side:** Each component has 20 `npb_style_variants` records. When a user selects a variant in the builder, the `variant_id` is stored on the `npb_sections` record.

2. **API side:** The section data includes the `variant_id` field. The variant's style data is merged with the section's style overrides (section overrides win).

3. **Next.js side:** The component registry maps each section type to a React component. That component receives the `variant_id` and renders accordingly. Variants can differ in:
   - Layout structure (centered, split, asymmetric, multi-column)
   - Visual treatment (background patterns, gradients, overlays)
   - Animation style (fade, slide, parallax)
   - Element positioning and sizing
   - Default color relationships (light bg vs dark bg)

### Style Variant Data Model

```json
{
  "component_slug": "hero",
  "variant_slug": "variant-03",
  "name": "Split Layout with Form",
  "style": {
    "layout": "split-right",
    "imagePosition": "right",
    "contentWidth": "50%",
    "showForm": true,
    "bgType": "gradient",
    "bgGradient": "linear-gradient(135deg, var(--color-primary-dark) 0%, var(--color-primary) 100%)",
    "titleAlign": "left",
    "badgeStyle": "pill",
    "ctaLayout": "stacked"
  },
  "preview_image": "/variants/hero/variant-03.jpg"
}
```

The `style` JSON in a variant contains layout-level decisions. These are not CSS overrides -- they are structural choices that the React component uses to conditionally render different HTML/Tailwind structures.

### Component Categories and Their 20 Variants

Each of the following component categories has 20 style variants. The variants are described by their layout archetype:

**Hero (20 variants)**
1. Full-screen centered text, background image
2. Full-screen centered text with CTA form below
3. Split: text left, image right
4. Split: image left, text right
5. Split: text left, form right
6. Video background with centered text
7. Parallax background with centered text
8. Gradient background, no image
9. Diagonal split (image + color)
10. Minimal: short hero, no background image
11. Full-screen with animated background particles
12. Carousel/slider hero (multiple slides)
13. Text only on dark background
14. Image collage behind text
15. Full-screen with bottom stats bar
16. Asymmetric: large text overlapping image
17. Full-screen with side vertical badges
18. Two-column: text + testimonial card
19. Full-screen with floating service cards
20. Animated gradient mesh background

**Services/Features Grid (20 variants)**
1. 3-column card grid with icons
2. 4-column card grid with icons
3. 2-column card grid with images
4. Icon list (vertical, no cards)
5. Alternating image + text rows
6. Horizontal scrolling cards
7. Masonry grid with images
8. Circular icon cards
9. Numbered list with descriptions
10. Tab-based (click tab to show service detail)
11. Accordion-based
12. Cards with hover overlay effect
13. Cards with top image and bottom text
14. Large featured card + small grid
15. Timeline-style services
16. Interactive cards (flip on hover)
17. Split: left navigation, right content
18. Magazine-style mixed layout
19. Cards with animated border gradient
20. Minimal text-only list

**Testimonials (20 variants)**
1. Horizontal slider (one at a time)
2. Grid (3 columns)
3. Single large quote with avatar
4. Cards with star ratings
5. Masonry layout
6. Horizontal scrolling strip
7. Alternating left/right quotes
8. Video testimonial grid
9. Quote carousel with background image
10. Cards with customer photo backgrounds
11. Minimal text quotes (no cards)
12. Timeline-style testimonials
13. Large centered quote with side navigation
14. Stacked full-width testimonials
15. Floating cards with parallax
16. Quote wall (pinterest-style)
17. Single rotating testimonial with company logos
18. Split: quote left, stats right
19. Animated typewriter quote
20. Cards with gradient accent borders

**CTA/Banners (20 variants)**
1. Full-width gradient with centered text
2. Full-width background image with overlay
3. Split: text left, image right
4. Compact inline banner
5. Diagonal section divider with CTA
6. Floating card CTA (overlapping sections)
7. Dark background with accent button
8. Parallax background CTA
9. Two-column: benefits list + CTA
10. Countdown timer CTA
11. CTA with testimonial quote
12. Sticky bottom bar CTA
13. Modal/popup CTA (triggered on scroll)
14. Animated gradient background
15. CTA with form embed
16. Video background CTA
17. Minimal text-only with underlined link
18. CTA with icon grid
19. Full-screen CTA with background pattern
20. CTA with animated statistics

*The same pattern continues for all component categories: Pricing (20), Team (20), FAQ (20), Gallery (20), Stats/Counters (20), Contact/Map (20), Timeline (20), Portfolio (20), Blog/News (20), Newsletter (20), About/Values (20), Zone/Area (20), Before/After (20), Rich Text (20), Divider/Spacer (20).*

---

## 9. Form Builder Architecture

### Form Field Types

| Field Type | Input Element | Validation Options |
|---|---|---|
| `text` | `<input type="text">` | required, minLength, maxLength, pattern |
| `email` | `<input type="email">` | required, custom domain blacklist |
| `phone` | `<input type="tel">` | required, pattern (with country presets) |
| `textarea` | `<textarea>` | required, minLength, maxLength, rows |
| `select` | `<select>` | required, options array, default value |
| `radio` | `<input type="radio">` group | required, options array |
| `checkbox` | `<input type="checkbox">` | required (must be checked), label |
| `checkbox_group` | Multiple checkboxes | required (min selections), options array |
| `file` | `<input type="file">` | required, accept (MIME types), maxSize, multiple |
| `date` | `<input type="date">` | required, min, max, disabledDates |
| `time` | `<input type="time">` | required, min, max |
| `number` | `<input type="number">` | required, min, max, step |
| `hidden` | `<input type="hidden">` | value (can include template vars like `{page_url}`) |
| `html` | Raw HTML block | -- (display only) |
| `divider` | `<hr>` | -- (display only) |
| `heading` | `<h3>` / `<h4>` | -- (display only, text content) |

### Form Field Data Model

```json
{
  "id": "field_abc123",
  "type": "text",
  "name": "full_name",
  "label": "Full Name",
  "placeholder": "John Doe",
  "required": true,
  "validation": {
    "minLength": 2,
    "maxLength": 100,
    "pattern": null,
    "customMessage": "Please enter your full name"
  },
  "width": "50%",
  "conditional": {
    "action": "show",
    "logic": "all",
    "rules": [
      { "field": "service_type", "operator": "equals", "value": "emergency" }
    ]
  },
  "defaultValue": "",
  "helpText": "As it appears on your ID",
  "cssClass": ""
}
```

### Multi-Step Form Support

Multi-step forms are defined by step boundaries in the field array:

```json
{
  "multi_step": {
    "enabled": true,
    "steps": [
      { "id": "step-1", "title": "Your Details", "description": "Tell us about yourself", "icon": "user" },
      { "id": "step-2", "title": "Service Needed", "description": "What do you need help with?", "icon": "wrench" },
      { "id": "step-3", "title": "Schedule", "description": "Pick a time", "icon": "calendar" }
    ],
    "progress_style": "bar",
    "allow_back": true,
    "validate_per_step": true
  },
  "fields": [
    { "id": "...", "step": "step-1", ... },
    { "id": "...", "step": "step-1", ... },
    { "id": "...", "step": "step-2", ... },
    { "id": "...", "step": "step-2", ... },
    { "id": "...", "step": "step-3", ... }
  ]
}
```

The Next.js form component renders one step at a time, validates fields per step before allowing navigation to the next step, and shows a progress indicator.

### Conditional Logic

Conditional logic allows showing/hiding fields based on other field values:

```json
{
  "conditional": {
    "action": "show",
    "logic": "any",
    "rules": [
      { "field": "service_type", "operator": "equals", "value": "emergency" },
      { "field": "service_type", "operator": "equals", "value": "urgent" }
    ]
  }
}
```

Supported operators: `equals`, `not_equals`, `contains`, `not_contains`, `starts_with`, `ends_with`, `greater_than`, `less_than`, `is_empty`, `is_not_empty`.

Logic mode: `all` (AND) or `any` (OR).

Action: `show` or `hide`.

### File Uploads

File uploads are handled in two stages:

1. **Upload stage:** The Next.js form component uploads files to the Next.js API route `/api/upload`, which validates the file (type, size, content headers), stores it temporarily, and returns a file reference ID.

2. **Submit stage:** The form submission includes file reference IDs. The Next.js API route forwards the form data + file references to the WordPress API, which moves files to the WordPress uploads directory and links them to the form submission.

File validation:
- Allowed MIME types (configurable per field, default: images, PDF, DOC/DOCX)
- Max file size (configurable, default: 10MB)
- Max total upload size per submission (configurable, default: 25MB)
- Content-type header verification (not just extension checking)
- Optional virus scanning integration point (ClamAV or external API)

### Spam Protection

Layered spam protection (all configurable per form):

1. **Honeypot** (always active) -- hidden field that bots fill out. Already proven in the existing codebase.
2. **Time-based** -- reject submissions that complete in under 3 seconds (configurable threshold)
3. **reCAPTCHA v3** (optional) -- invisible score-based, threshold configurable (default: 0.5)
4. **Cloudflare Turnstile** (optional) -- privacy-friendly alternative to reCAPTCHA
5. **Rate limiting** -- max N submissions per IP per hour (default: 5)
6. **Blocklist** -- block specific IP addresses, email domains, or keywords

### Email Notifications

```json
{
  "notifications": {
    "admin": {
      "enabled": true,
      "to": ["admin@example.com"],
      "cc": [],
      "subject": "New {form_name} submission from {field:full_name}",
      "template": "default",
      "reply_to": "{field:email}"
    },
    "user_confirmation": {
      "enabled": true,
      "to": "{field:email}",
      "subject": "Thank you for contacting {site_name}",
      "template": "confirmation",
      "delay": 0
    }
  }
}
```

Emails are sent via `wp_mail()` using HTML templates stored in the plugin. Template variables like `{field:full_name}`, `{form_name}`, `{site_name}`, `{submission_date}` are replaced at send time.

### Integration Hooks

The form builder fires WordPress actions at key points:

```php
do_action('npb_form_submitted', $form_id, $submission_data, $submission_id);
do_action('npb_form_notification_sent', $form_id, $notification_type, $recipient);
do_action('npb_form_file_uploaded', $form_id, $field_name, $file_path);
```

Built-in integrations (optional, configured per form):
- **Mailchimp** -- subscribe to list on submission
- **ConvertKit** -- add subscriber on submission
- **Webhook** -- POST form data to any URL (Zapier, Make, n8n compatible)
- **Google Sheets** -- append row to spreadsheet (via Sheets API)

---

## 10. Theme System

### Color Palette Management

The theme system uses a token-based approach. Colors are defined as named tokens that map to CSS custom properties.

**Core color tokens:**

```json
{
  "colors": {
    "primary": { "value": "#1E3A5F", "label": "Primary" },
    "primary-dark": { "value": "#152C4A", "label": "Primary Dark", "auto": "darken(primary, 15%)" },
    "primary-light": { "value": "#2E5C8A", "label": "Primary Light", "auto": "lighten(primary, 20%)" },
    "accent": { "value": "#D4942A", "label": "Accent" },
    "accent-dark": { "value": "#B87D1E", "label": "Accent Dark", "auto": "darken(accent, 15%)" },
    "dark": { "value": "#1A202C", "label": "Dark" },
    "secondary": { "value": "#2D3748", "label": "Secondary" },
    "gray-50": { "value": "#F9FAFB", "label": "Gray 50" },
    "gray-100": { "value": "#F3F4F6", "label": "Gray 100" },
    "gray-200": { "value": "#E5E7EB", "label": "Gray 200" },
    "gray-300": { "value": "#D1D5DB", "label": "Gray 300" },
    "gray-400": { "value": "#9CA3AF", "label": "Gray 400" },
    "gray-500": { "value": "#6B7280", "label": "Gray 500" },
    "gray-600": { "value": "#4B5563", "label": "Gray 600" },
    "gray-700": { "value": "#374151", "label": "Gray 700" },
    "gray-800": { "value": "#1F2937", "label": "Gray 800" },
    "gray-900": { "value": "#111827", "label": "Gray 900" },
    "success": { "value": "#10B981", "label": "Success" },
    "warning": { "value": "#F59E0B", "label": "Warning" },
    "error": { "value": "#EF4444", "label": "Error" },
    "info": { "value": "#3B82F6", "label": "Info" },
    "white": { "value": "#FFFFFF", "label": "White" },
    "black": { "value": "#000000", "label": "Black" }
  }
}
```

Colors with an `"auto"` property are automatically computed from a source color but can be manually overridden. The admin UI shows a color palette editor with visual swatches and a harmony checker.

### Typography System

```json
{
  "typography": {
    "heading": {
      "family": "Montserrat",
      "source": "google",
      "weights": [600, 700, 800, 900],
      "fallback": "system-ui, sans-serif"
    },
    "body": {
      "family": "Open Sans",
      "source": "google",
      "weights": [300, 400, 500, 600, 700],
      "fallback": "system-ui, sans-serif"
    },
    "mono": {
      "family": "JetBrains Mono",
      "source": "google",
      "weights": [400, 500],
      "fallback": "monospace"
    },
    "scale": {
      "xs": { "size": "12px", "lineHeight": "16px" },
      "sm": { "size": "14px", "lineHeight": "20px" },
      "base": { "size": "16px", "lineHeight": "24px" },
      "lg": { "size": "18px", "lineHeight": "28px" },
      "xl": { "size": "20px", "lineHeight": "28px" },
      "2xl": { "size": "24px", "lineHeight": "32px" },
      "3xl": { "size": "30px", "lineHeight": "36px" },
      "4xl": { "size": "36px", "lineHeight": "40px" },
      "5xl": { "size": "48px", "lineHeight": "1" },
      "6xl": { "size": "60px", "lineHeight": "1" },
      "7xl": { "size": "72px", "lineHeight": "1" }
    }
  }
}
```

The Google Fonts integration in the admin UI provides a searchable font picker that loads a preview of any Google Font. Selected fonts are passed to the Next.js frontend, which loads them via `next/font/google` for optimal performance.

### Spacing/Sizing Tokens

```json
{
  "spacing": {
    "base": 4,
    "scale": {
      "0": "0px",
      "1": "4px",
      "2": "8px",
      "3": "12px",
      "4": "16px",
      "5": "20px",
      "6": "24px",
      "8": "32px",
      "10": "40px",
      "12": "48px",
      "16": "64px",
      "20": "80px",
      "24": "96px",
      "28": "112px",
      "32": "128px"
    },
    "sectionPadding": {
      "compact": { "mobile": "48px", "tablet": "64px", "desktop": "80px" },
      "default": { "mobile": "64px", "tablet": "80px", "desktop": "112px" },
      "spacious": { "mobile": "80px", "tablet": "112px", "desktop": "144px" }
    },
    "containerMaxWidth": "1280px",
    "containerPadding": { "mobile": "16px", "tablet": "24px", "desktop": "32px" }
  }
}
```

### Dark Mode Support

Dark mode is defined as a color mapping overlay. When dark mode is active, all color tokens are remapped:

```json
{
  "dark_mode": {
    "enabled": true,
    "toggle": "system",
    "colors": {
      "primary": "#4A90D9",
      "primary-dark": "#3A7BC8",
      "primary-light": "#5AA0E9",
      "dark": "#F9FAFB",
      "secondary": "#E5E7EB",
      "gray-50": "#111827",
      "gray-100": "#1F2937",
      "gray-900": "#F9FAFB",
      "white": "#0F172A",
      "black": "#FFFFFF"
    }
  }
}
```

Toggle modes: `"system"` (follows OS preference), `"manual"` (user toggle), `"disabled"`.

### CSS Variable Architecture

The theme outputs a flat map of CSS custom properties that the Next.js frontend injects into the `<html>` element (matching the existing pattern in `layout.tsx`):

```css
:root {
  --color-primary: #1E3A5F;
  --color-primary-dark: #152C4A;
  --color-primary-light: #2E5C8A;
  --color-accent: #D4942A;
  --color-accent-dark: #B87D1E;
  --color-dark: #1A202C;
  --color-secondary: #2D3748;
  /* ... all color tokens ... */

  --font-heading: 'Montserrat', system-ui, sans-serif;
  --font-body: 'Open Sans', system-ui, sans-serif;
  --font-mono: 'JetBrains Mono', monospace;

  --spacing-base: 4px;
  --container-max-width: 1280px;

  --radius-sm: 4px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-xl: 16px;
  --radius-full: 9999px;

  --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
  --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
  --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
  --shadow-xl: 0 20px 25px rgba(0,0,0,0.1);
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  :root[data-theme="auto"], :root[data-theme="dark"] {
    --color-primary: #4A90D9;
    /* ... dark overrides ... */
  }
}
```

The Tailwind CSS configuration on the Next.js side references these CSS variables so that all utility classes automatically use theme values.

### Theme Import/Export

Themes export as JSON files containing all token definitions. Import validates the JSON schema and creates a new `npb_themes` record. This enables theme sharing between sites and within the community.

---

## 11. REST API Design

### API Namespace and Versioning

Base URL: `/wp-json/npb/v1/`

Version is in the URL path. When breaking changes are needed, a `/npb/v2/` namespace is introduced while `/v1/` continues to work for backward compatibility.

### All Endpoints Organized by Module

**Site/Global (no module prefix)**
```
GET    /npb/v1/site-config                     -- business info, contact details, coordinates
GET    /npb/v1/health                           -- API health check + version info
```

**Pages (`page-builder`)**
```
GET    /npb/v1/pages                            -- list all published pages (with pagination)
GET    /npb/v1/pages/{slug}                     -- get page by slug (sections, SEO, settings)
GET    /npb/v1/pages/{slug}/sections            -- get sections only
POST   /npb/v1/pages                            -- create page (auth required)
PUT    /npb/v1/pages/{id}                       -- update page (auth required)
DELETE /npb/v1/pages/{id}                       -- delete page (auth required)
POST   /npb/v1/pages/{id}/duplicate             -- duplicate page (auth required)
POST   /npb/v1/pages/{id}/sections/reorder      -- reorder sections (auth required)
POST   /npb/v1/pages/import                     -- import page from JSON (auth required)
GET    /npb/v1/pages/{id}/export                -- export page as JSON (auth required)
```

**Headers & Footers (`header-builder`, `footer-builder`)**
```
GET    /npb/v1/headers                          -- list all headers
GET    /npb/v1/headers/{id}                     -- get header by ID
POST   /npb/v1/headers                          -- create header (auth required)
PUT    /npb/v1/headers/{id}                     -- update header (auth required)
DELETE /npb/v1/headers/{id}                     -- delete header (auth required)
GET    /npb/v1/footers                          -- list all footers
GET    /npb/v1/footers/{id}                     -- get footer by ID
POST   /npb/v1/footers                          -- create footer (auth required)
PUT    /npb/v1/footers/{id}                     -- update footer (auth required)
DELETE /npb/v1/footers/{id}                     -- delete footer (auth required)
```

**Theme (`theme-manager`)**
```
GET    /npb/v1/theme                            -- get active theme (colors, typography, spacing, buttons)
GET    /npb/v1/themes                           -- list all themes (auth required)
POST   /npb/v1/themes                           -- create theme (auth required)
PUT    /npb/v1/themes/{id}                      -- update theme (auth required)
PUT    /npb/v1/themes/{id}/activate             -- set as active theme (auth required)
DELETE /npb/v1/themes/{id}                      -- delete theme (auth required)
POST   /npb/v1/themes/import                    -- import theme from JSON (auth required)
GET    /npb/v1/themes/{id}/export               -- export theme as JSON (auth required)
```

**Forms (`form-builder`)**
```
GET    /npb/v1/forms/{slug}                     -- get form definition (fields, settings, styling)
POST   /npb/v1/forms/{slug}/submit              -- submit form (public, rate limited)
GET    /npb/v1/forms                            -- list all forms (auth required)
POST   /npb/v1/forms                            -- create form (auth required)
PUT    /npb/v1/forms/{id}                       -- update form (auth required)
DELETE /npb/v1/forms/{id}                       -- delete form (auth required)
GET    /npb/v1/forms/{id}/submissions           -- list submissions (auth required, paginated)
GET    /npb/v1/forms/{id}/submissions/{sid}     -- get single submission (auth required)
PUT    /npb/v1/forms/{id}/submissions/{sid}     -- update submission status (auth required)
DELETE /npb/v1/forms/{id}/submissions/{sid}     -- delete submission (auth required)
GET    /npb/v1/forms/{id}/submissions/export    -- export submissions as CSV (auth required)
```

**SEO (`seo-manager`)**
```
GET    /npb/v1/seo/global                       -- global SEO settings
GET    /npb/v1/seo/page/{slug}                  -- page-specific SEO data
GET    /npb/v1/seo/sitemap                      -- sitemap data (for Next.js to render XML)
GET    /npb/v1/seo/redirects                    -- list of redirects (for Next.js middleware)
PUT    /npb/v1/seo/global                       -- update global SEO (auth required)
PUT    /npb/v1/seo/page/{id}                    -- update page SEO (auth required)
POST   /npb/v1/seo/redirects                    -- create redirect (auth required)
DELETE /npb/v1/seo/redirects/{id}               -- delete redirect (auth required)
```

**Schema (`schema-manager`)**
```
GET    /npb/v1/schema/page/{slug}               -- get JSON-LD for page
GET    /npb/v1/schema/global                    -- get site-wide schema (Organization, etc.)
PUT    /npb/v1/schema/page/{id}                 -- update page schema overrides (auth required)
PUT    /npb/v1/schema/global                    -- update global schema settings (auth required)
```

**Components (`component-library`)**
```
GET    /npb/v1/components                       -- list all components with categories
GET    /npb/v1/components/{slug}                -- get component details + content schema
GET    /npb/v1/components/{slug}/variants       -- list style variants for component
POST   /npb/v1/components                       -- create user component (auth required)
PUT    /npb/v1/components/{id}                  -- update component (auth required)
DELETE /npb/v1/components/{id}                  -- delete user component (auth required)
```

**Navigation (`navigation-manager`)**
```
GET    /npb/v1/navigation                       -- get all menus
GET    /npb/v1/navigation/{slug}                -- get specific menu
POST   /npb/v1/navigation                       -- create menu (auth required)
PUT    /npb/v1/navigation/{id}                  -- update menu (auth required)
DELETE /npb/v1/navigation/{id}                  -- delete menu (auth required)
```

**Buttons (`button-manager`)**
```
GET    /npb/v1/buttons                          -- list all button presets
GET    /npb/v1/buttons/{slug}                   -- get button preset
POST   /npb/v1/buttons                          -- create preset (auth required)
PUT    /npb/v1/buttons/{id}                     -- update preset (auth required)
DELETE /npb/v1/buttons/{id}                     -- delete preset (auth required)
```

**Templates (`template-library`)**
```
GET    /npb/v1/templates                        -- list all templates
GET    /npb/v1/templates/{slug}                 -- get template details + preview
POST   /npb/v1/templates/{slug}/import          -- import template (auth required)
POST   /npb/v1/templates/export                 -- export current site as template (auth required)
```

**Analytics (`analytics-integration`)**
```
GET    /npb/v1/analytics/config                 -- get analytics configuration (GA ID, GTM ID, etc.)
PUT    /npb/v1/analytics/config                 -- update config (auth required)
```

**Revalidation (`revalidation-manager`)**
```
POST   /npb/v1/revalidate/trigger               -- manually trigger revalidation (auth required)
GET    /npb/v1/revalidate/log                   -- get revalidation log (auth required)
GET    /npb/v1/revalidate/status                -- check frontend connectivity (auth required)
```

**Settings (core)**
```
GET    /npb/v1/settings                         -- get all plugin settings (auth required)
PUT    /npb/v1/settings                         -- update settings (auth required)
GET    /npb/v1/settings/{group}                 -- get settings by group (auth required)
```

### Authentication & Security

**Public endpoints** (GET requests for published content): No authentication required. Protected by rate limiting and caching.

**Admin endpoints** (POST/PUT/DELETE): Require one of:
1. **WordPress Application Passwords** (recommended for Next.js server-to-server) -- passed via HTTP Basic Auth
2. **WordPress Nonce + Cookie** (for the React admin UI running inside wp-admin) -- standard WordPress auth
3. **JWT Tokens** (optional, for external integrations) -- issued via a `/npb/v1/auth/token` endpoint

### Rate Limiting

- Public GET endpoints: 60 requests/minute per IP
- Form submissions: 5 submissions/minute per IP per form
- Admin endpoints: 120 requests/minute per authenticated user
- Rate limit headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`

### Caching Strategy

- Public GET endpoints set `Cache-Control: public, max-age=60, s-maxage=300` by default
- Theme and component endpoints use longer TTLs: `s-maxage=3600`
- Admin endpoints: `Cache-Control: no-store`
- WordPress object cache (Memcached/Redis) used for expensive queries
- ETags for conditional requests (304 Not Modified)

### Webhook System for Revalidation

When content changes in WordPress, the Revalidation Manager module:

1. Determines affected paths (page slug, related pages like index)
2. Queues a webhook POST to the configured Next.js revalidation URL
3. Sends: `{ "secret": "...", "paths": ["/", "/services/plumbing"], "reason": "page_updated", "timestamp": 1711843200 }`
4. Logs the result (success/failure, response code, response time)
5. Retries on failure (3 attempts, exponential backoff: 1s, 5s, 30s)

---

## 12. Next.js Frontend Architecture

### App Router Structure

The Next.js frontend uses the App Router with dynamic page rendering driven entirely by the WordPress API data.

```
src/
  app/
    layout.tsx                  -- root layout: fetches theme, nav, header, footer from API
    page.tsx                    -- home page: fetches page sections for slug "home"
    [...slug]/
      page.tsx                  -- catch-all dynamic route: renders any page from WP
    api/
      revalidate/
        route.ts                -- on-demand ISR revalidation endpoint
      preview/
        route.ts                -- preview mode for the builder
      forms/
        [slug]/
          submit/
            route.ts            -- form submission proxy to WP
      upload/
        route.ts                -- file upload handler
    not-found.tsx
    error.tsx
    loading.tsx
    sitemap.ts                  -- dynamic sitemap from WP API
    robots.ts                   -- dynamic robots.txt from WP API
  components/
    registry/
      ComponentRegistry.ts      -- maps section type strings to React components
      index.ts
    sections/                   -- one component per section type
      Hero/
        Hero.tsx                -- main component, switches on variant
        variants/
          Variant01.tsx
          Variant02.tsx
          ...
          Variant20.tsx
        Hero.types.ts
        index.ts
      ServicesGrid/
        ...
      Testimonials/
        ...
      CTABanner/
        ...
      ContactForm/
        ...
      (... one directory per component type ...)
    layout/
      Header/
        Header.tsx              -- renders based on header data from API
        variants/
          StandardHeader.tsx
          CenteredHeader.tsx
          SplitNavHeader.tsx
          TransparentHeader.tsx
        MobileMenu.tsx
      Footer/
        Footer.tsx
        variants/
          StandardFooter.tsx
          MinimalFooter.tsx
          MegaFooter.tsx
    forms/
      DynamicForm.tsx           -- renders any form from form config JSON
      FormField.tsx             -- renders individual field by type
      MultiStepForm.tsx         -- multi-step wrapper
      ConditionalField.tsx      -- handles show/hide logic
      FileUpload.tsx
    seo/
      JsonLd.tsx                -- renders schema from API data
      Breadcrumbs.tsx
    ui/
      Button.tsx                -- renders button from preset data
      SectionWrapper.tsx        -- responsive CSS injection (existing pattern)
      AnimatedSection.tsx
      SectionTitle.tsx
      Container.tsx
  lib/
    api/
      client.ts                 -- base API client with error handling + caching
      pages.ts                  -- page-related API functions
      theme.ts                  -- theme API functions
      forms.ts                  -- form API functions
      seo.ts                    -- SEO API functions
      schema.ts                 -- schema API functions
      navigation.ts             -- navigation API functions
      site.ts                   -- site config API functions
    utils/
      cn.ts                     -- classnames utility
      responsive.ts             -- responsive value helpers
      sanitize.ts               -- client-side sanitization
    hooks/
      useForm.ts                -- form state management hook
      useAnimation.ts           -- intersection observer + animation hook
      useBreakpoint.ts          -- current breakpoint detection
    types/
      api.ts                    -- all TypeScript interfaces for API responses
      sections.ts               -- section-specific types
      theme.ts                  -- theme types
      forms.ts                  -- form types
  styles/
    globals.css                 -- Tailwind directives + custom utilities
    theme.css                   -- CSS variable declarations (generated from API)
  middleware.ts                 -- security headers, redirects from WP API
```

### Dynamic Page Rendering from WP Data

The catch-all `[...slug]/page.tsx` handles all dynamic pages:

```typescript
// Pseudocode for the catch-all page
export default async function DynamicPage({ params }) {
  const slug = params.slug?.join('/') || 'home';
  const [page, theme, siteConfig] = await Promise.all([
    getPage(slug),
    getActiveTheme(),
    getSiteConfig(),
  ]);

  if (!page) notFound();

  return (
    <SectionRenderer
      sections={page.sections}
      sharedData={{ siteConfig, theme }}
    />
  );
}

export async function generateMetadata({ params }) {
  const slug = params.slug?.join('/') || 'home';
  const seo = await getPageSEO(slug);
  return buildMetadata(seo);
}
```

### Component Registry

The component registry maps section type strings from the API to React components:

```typescript
// Pseudocode for the registry
const registry: Record<string, React.ComponentType<SectionProps>> = {
  'hero': HeroSection,
  'services_grid': ServicesGridSection,
  'testimonials': TestimonialsSection,
  'cta_banner': CTABannerSection,
  'contact_form': ContactFormSection,
  'faq': FAQSection,
  'pricing': PricingSection,
  'team': TeamSection,
  'gallery': GallerySection,
  'stats_counter': StatsCounterSection,
  'timeline': TimelineSection,
  'before_after': BeforeAfterSection,
  'zone_intervention': ZoneInterventionSection,
  'rich_text': RichTextSection,
  'newsletter': NewsletterSection,
  'portfolio': PortfolioSection,
  'about': AboutSection,
  'values_grid': ValuesGridSection,
  'map': MapSection,
  'divider': DividerSection,
  'embed': EmbedSection,
  // ... all section types
};
```

Each section component internally handles its own variant switching. The component receives the `variant_id` and uses it to select the appropriate layout sub-component.

### ISR + On-Demand Revalidation Strategy

- **Default revalidation:** `revalidate: 60` on all API fetches (1 minute)
- **On-demand revalidation:** WordPress sends a webhook to `/api/revalidate` when content changes, which calls `revalidatePath()` or `revalidateTag()` for affected pages
- **Tag-based invalidation:** API fetches are tagged (`next: { tags: ['page-home', 'theme', 'navigation'] }`) so specific caches can be invalidated without revalidating everything
- **Static generation at build time:** Pages with known slugs are generated at build time via `generateStaticParams()`, fetching the page list from the WP API
- **Fallback:** `dynamicParams: true` so new pages added in WP are rendered on first request and then cached

### Image Optimization Pipeline

1. Images are uploaded to WordPress Media Library
2. WordPress stores original + generates standard sizes
3. The API returns image URLs from the WordPress media server
4. Next.js `<Image>` component handles:
   - Automatic WebP/AVIF conversion via `next/image`
   - Responsive `srcset` generation
   - Lazy loading (default) with `loading="eager"` for above-the-fold
   - `priority` prop for LCP images (hero images, first visible image)
   - Blur placeholder from low-quality image preview (LQIP) generated by WordPress
5. Remote patterns configured in `next.config.ts` to allow WordPress domain images

### CSS/Styling Approach

**Tailwind CSS 4 + CSS Custom Properties:**

- Tailwind is the primary styling mechanism
- All theme values (colors, fonts, spacing) are CSS custom properties set on `<html>`
- Tailwind config references these variables so utility classes use theme values
- Component variants use Tailwind classes for layout/structure
- Section style overrides use inline styles for dynamic colors and `<style>` tags for responsive values (matching the existing `SectionWrapper` pattern)
- Framer Motion for animations (already in the existing codebase)
- No CSS-in-JS runtime -- everything compiles to static CSS at build time

### Performance Optimization

- **Code splitting:** Each section variant is a separate chunk loaded on demand via `dynamic()` imports
- **Tree shaking:** Only the section types and variants used on a page are included in that page's bundle
- **Font loading:** `next/font/google` with `display: swap` and `preload` (matching existing pattern)
- **Image loading:** Priority for hero images, lazy for everything else
- **Bundle analysis:** `@next/bundle-analyzer` in development
- **Minimal client-side JS:** Section components are Server Components by default; only interactive components (forms, sliders, animations) use `"use client"`
- **Streaming:** React Suspense boundaries for non-critical sections

### SEO Implementation

- `generateMetadata()` on every page, fetching SEO data from the WP API
- Canonical URLs, Open Graph, Twitter Cards from API data
- JSON-LD schema injected via `<script type="application/ld+json">`
- Dynamic `sitemap.ts` that generates sitemap from WP API
- Dynamic `robots.ts` from WP API
- Breadcrumb component with BreadcrumbList schema
- `<link rel="alternate" hreflang="...">` for multilingual support (future)

### Accessibility (WCAG 2.1 AA)

- All components use semantic HTML (`<nav>`, `<main>`, `<section>`, `<article>`, `<header>`, `<footer>`)
- All images have alt text (enforced by Media Manager module)
- All form fields have associated labels
- Color contrast ratios meet AA standards (enforced by theme color picker with contrast checker)
- Keyboard navigation for all interactive elements
- ARIA attributes where needed (mobile menu, modals, accordions, tabs)
- Focus management (focus trap in modals, skip-to-content link)
- Reduced motion support (`prefers-reduced-motion` media query disables Framer Motion animations)
- Screen reader announcements for form submissions and dynamic content changes

---

## 13. Security Architecture

### WordPress Security

**Input sanitization -- every input goes through the Sanitizer class:**
- `sanitize_text_field()` for plain text
- `wp_kses_post()` for HTML content
- `esc_url_raw()` for URLs
- `sanitize_hex_color()` for colors
- `absint()` for integers
- JSON inputs validated against schemas before storage

**Capability checks on all admin endpoints:**
```php
if (!current_user_can('npb_edit_pages')) {
    return new WP_REST_Response(['message' => 'Unauthorized'], 403);
}
```

Custom capabilities:
- `npb_edit_pages` -- create/edit/delete pages and sections
- `npb_manage_themes` -- create/edit/delete themes
- `npb_manage_forms` -- create/edit/delete forms, view submissions
- `npb_manage_settings` -- change plugin settings
- `npb_manage_templates` -- import/export templates

Default mapping: `administrator` gets all capabilities. `editor` gets `npb_edit_pages` and `npb_manage_forms`.

**Nonce verification** on all admin AJAX and REST API writes (standard WordPress nonce system).

**SQL injection prevention** -- all database queries use `$wpdb->prepare()`. No raw string concatenation in queries.

**File upload security:**
- MIME type verification (not just extension checking)
- File size limits
- Rename uploaded files (strip original filename)
- Store outside web root when possible
- Serve via PHP with proper headers (no direct URL access to uploads)

### API Authentication

**Next.js to WordPress (server-to-server):**
- WordPress Application Passwords (built into WordPress 5.6+)
- Passed via HTTP Basic Auth header
- Only needed for admin/write operations
- Public read endpoints require no auth

**Admin UI to WordPress:**
- Standard WordPress cookie + nonce authentication
- REST API endpoints verify nonce via `X-WP-Nonce` header

**Optional JWT (for external integrations):**
- Token endpoint: `POST /npb/v1/auth/token` with username/password
- Short-lived tokens (1 hour)
- Refresh token support
- Token stored in httpOnly cookie for admin SPA

### Next.js Security Headers

Applied via middleware (extending the existing pattern in `middleware.ts`):

```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 0 (deprecated, rely on CSP instead)
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=()
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: [per-page, configured from WP]
```

CSP is dynamic -- the WP API provides CSP directives based on which scripts/embeds are configured (Google Maps, YouTube, reCAPTCHA, analytics).

### XSS Prevention

- All user-generated content is sanitized on input (WordPress side) and escaped on output (React automatically escapes JSX expressions)
- `dangerouslySetInnerHTML` is used only for JSON-LD scripts and inline CSS from the style system -- both of which are server-generated, never user-input
- CSP headers prevent inline script execution except for specific hashes

### CSRF Prevention

- WordPress nonces on all mutation endpoints from the admin UI
- API secret verification on the revalidation endpoint
- SameSite cookies

### Secret Management

- All secrets stored in environment variables, never in code or database
- WordPress side: `wp-config.php` constants (REVALIDATE_SECRET, etc.)
- Next.js side: `.env.local` variables (WORDPRESS_API_URL, WORDPRESS_REVALIDATE_SECRET, etc.)
- Secrets are never exposed in API responses or client-side JavaScript
- Admin UI communicates only with its own WordPress backend (same origin)

---

## 14. Performance Strategy

### Target: PageSpeed 95+

The existing codebase already follows best practices. The NextPress Builder framework enforces these patterns:

### Image Optimization

- **Format:** Next.js `<Image>` automatically serves WebP/AVIF based on browser support
- **Sizing:** `srcset` with multiple widths (640, 750, 828, 1080, 1200, 1920)
- **Loading:** `loading="lazy"` default, `priority` on hero/LCP images
- **Dimensions:** Width and height always specified (prevents CLS)
- **Placeholders:** Blur placeholder using LQIP data URL from WordPress
- **CDN:** Images served through Next.js image optimization (or external CDN if configured)

### Font Loading

- `next/font/google` with `display: swap` (no layout shift, fast first paint)
- Only the weights used by the theme are loaded (e.g., 400, 600, 700 not all 9 weights)
- Font files self-hosted by Next.js (no external requests to Google)
- `font-display: swap` ensures text is visible immediately with fallback font

### Critical CSS

- Tailwind CSS is tree-shaken at build time -- only used classes are in the bundle
- Above-the-fold sections are Server Components (CSS is inlined in the initial HTML)
- Below-the-fold section CSS is loaded with the section's lazy-loaded chunk
- The `SectionWrapper` pattern for responsive CSS generates minimal inline `<style>` tags

### Bundle Optimization

- Section variants are `dynamic()` imports -- only the used variant is loaded
- Client components (`"use client"`) are minimized -- only forms, sliders, and animated elements
- No jQuery, no unnecessary runtime libraries
- Framer Motion tree-shaken to only include used features
- `next/dynamic` with `ssr: false` for components that are never in the initial viewport (maps, heavy embeds)

### Caching Layers

```
Layer 1: WordPress Object Cache (Redis/Memcached)
  - Caches expensive DB queries (page sections, form configs)
  - TTL: 5 minutes (invalidated on content change)

Layer 2: WordPress REST API HTTP Cache
  - Cache-Control headers on responses
  - ETags for conditional requests
  - s-maxage for CDN caching

Layer 3: Next.js Data Cache
  - fetch() with revalidate: 60 (ISR)
  - Tag-based invalidation for precise cache busting
  - On-demand revalidation from WordPress webhooks

Layer 4: Next.js Full Route Cache
  - Statically generated pages cached on disk/CDN
  - Revalidated on demand or after TTL expires

Layer 5: CDN / Edge Cache (Vercel, Cloudflare, etc.)
  - Serves cached HTML from edge locations
  - Stale-while-revalidate for instant responses
  - Cache purge triggered by on-demand revalidation
```

### Performance Budget

| Metric | Target | Strategy |
|---|---|---|
| LCP | < 2.5s | Priority loading for hero images, inline critical CSS |
| FID/INP | < 100ms | Minimize client-side JS, defer non-critical interactions |
| CLS | < 0.1 | Explicit image dimensions, font display swap, no layout shifts |
| TTI | < 3.5s | Server Components, minimal client-side hydration |
| Total JS | < 150KB gzipped | Code splitting, tree shaking, dynamic imports |
| Total CSS | < 30KB gzipped | Tailwind tree shaking, no unused styles |
| First byte | < 200ms | ISR cached responses, edge delivery |

---

## 15. Development Roadmap

### Phase 1: Core Foundation (MVP) -- 8-10 weeks

**Goal:** Working plugin with basic page builder, theme system, and API. Proof of concept.

- WordPress plugin scaffold (PHP OOP structure, autoloading, module system)
- Core classes: Container, ModuleManager, DatabaseManager, RestApiManager, Sanitizer
- Database schema + migrations
- Theme Manager module (color palette, typography, spacing, CSS variable output)
- Page Builder module -- basic version (add/remove/reorder sections, content editing, no drag-and-drop yet)
- 5 core section components (Hero, Services Grid, CTA Banner, Testimonials, Contact Form)
- 1 variant per component (default)
- REST API: site-config, pages, theme, navigation
- Next.js starter: App Router, catch-all dynamic route, Component Registry, SectionWrapper, ISR
- Revalidation system (webhook from WP to Next.js)
- Basic admin UI (React in wp-admin, list pages, edit sections, edit theme)
- Security fundamentals (sanitization, capability checks, security headers)

**Deliverable:** A functional plugin + Next.js starter that can render dynamic pages from WordPress data.

### Phase 2: Builder & Templates -- 10-12 weeks

**Goal:** Full drag-and-drop builder, style variants, first templates.

- Drag-and-drop implementation (@dnd-kit in admin UI)
- Live preview iframe
- Style variant system (data model, variant picker in builder)
- 10 section component types with 5 variants each (50 total variants)
- Header Builder module (3 header layouts)
- Footer Builder module (3 footer layouts)
- Navigation Manager module
- Button/CTA Manager module
- First 5 business templates (restaurant, plumber, lawyer, dentist, electrician)
- One-click template import with customization wizard
- Responsive editing (breakpoint toggle in builder)
- Section visibility per breakpoint
- Page-level SEO fields in builder

**Deliverable:** A usable page builder with templates. Agencies can start building simple local business sites.

### Phase 3: Advanced Features -- 10-12 weeks

**Goal:** Form builder, full SEO, schema, remaining templates and variants.

- Form Builder module (all field types, multi-step, conditional logic, file uploads)
- Spam protection (honeypot, reCAPTCHA, Turnstile, rate limiting)
- Form submission storage + admin viewer
- Email notification system
- SEO Manager module (per-page SEO, sitemap, redirects, robots.txt)
- Schema/JSON-LD Manager module (auto-generation, per-page overrides, validation)
- Media Manager enhancements (focal point, alt text enforcement, external images)
- Remaining 15 templates (total: 20)
- All section components expanded to 20 variants each
- Component Library module (user-created reusable components)
- Import/Export system (pages, themes, templates)
- Dark mode support
- Analytics Integration module
- Performance Manager module

**Deliverable:** Feature-complete product. All 20 templates, all variants, all modules working.

### Phase 4: Community & Marketplace -- 8-10 weeks

**Goal:** Open source launch, documentation, community tools.

- Documentation site (Docusaurus or similar)
- Getting Started guide
- Developer documentation (module API, hooks/filters, custom components)
- User documentation (video tutorials, step-by-step guides)
- GitHub repository setup (contributing guidelines, issue templates, PR templates, CI/CD)
- Automated testing (PHPUnit for WordPress, Jest/Playwright for Next.js)
- Plugin directory submission (WordPress.org)
- npm package for the Next.js starter
- Community template repository (submit/download templates)
- Community variant gallery (submit/download style variants)
- Internationalization (i18n) for the admin UI
- Translation-ready strings in all PHP and React code

**Deliverable:** Publicly launched open source project with documentation and community infrastructure.

### Phase 5: Enterprise & Ecosystem -- Ongoing

**Goal:** Premium features, integrations, scalability.

- Multi-site support (one WordPress managing multiple Next.js frontends)
- Collaboration features (multi-user editing, revision history, content approval workflow)
- White-label mode (remove NextPress branding for agency use)
- WooCommerce integration (product sections, cart, checkout)
- Multilingual support (WPML/Polylang integration)
- A/B testing module (variant testing on sections)
- Custom code module (inject custom React components via code editor)
- Marketplace for premium templates, variants, and modules
- Performance monitoring dashboard (Core Web Vitals tracking)
- Hosting partnerships (one-click deploy to Vercel, Netlify, Cloudflare Pages)

---

## 16. Tech Stack Summary

### WordPress Side

| Component | Technology | Version |
|---|---|---|
| Language | PHP | 8.1+ |
| Framework | WordPress | 6.4+ |
| Autoloading | Composer PSR-4 | -- |
| Admin UI | React + @wordpress/scripts | -- |
| Drag & Drop | @dnd-kit/core, @dnd-kit/sortable | -- |
| Admin Components | @wordpress/components | -- |
| Color Picker | @wordpress/components ColorPicker | -- |
| Code Editor | @wordpress/code-editor (CodeMirror) | -- |
| Build Tool | @wordpress/scripts (webpack) | -- |
| Testing | PHPUnit + WP Test Utils | -- |
| Database | MySQL via $wpdb | 5.7+ / MariaDB 10.3+ |
| Caching | WP Object Cache API | -- |

### Next.js Side

| Component | Technology | Version |
|---|---|---|
| Framework | Next.js (App Router) | 15+ |
| Language | TypeScript | 5+ |
| React | React | 19+ |
| Styling | Tailwind CSS | 4+ |
| Animation | Framer Motion | 12+ |
| Icons | Lucide React | -- |
| Carousel | Swiper | 12+ |
| Forms | Custom hooks (no form library) | -- |
| Sitemap | next-sitemap | -- |
| Testing | Jest + React Testing Library + Playwright | -- |
| Linting | ESLint + eslint-config-next | -- |
| Formatting | Prettier | -- |

### DevOps

| Component | Technology |
|---|---|
| Version Control | Git + GitHub |
| CI/CD | GitHub Actions |
| Versioning | Semantic Versioning (semver) |
| PHP CI | PHPUnit, PHPCS (WordPress coding standards), PHPStan |
| JS CI | Jest, Playwright, ESLint |
| Release | GitHub Releases + WordPress.org SVN deploy |
| npm Package | Published to npm registry for Next.js starter |
| Docs | Docusaurus (hosted on GitHub Pages or Vercel) |

---

## 17. Open Source Strategy

### License

- **WordPress Plugin:** GPLv2 or later (required by WordPress.org)
- **Next.js Starter:** MIT License (maximizes adoption, compatible with any hosting)

### Repository Structure

Two repositories:
1. `nextpress-builder` -- the WordPress plugin (PHP + React admin)
2. `nextpress-starter` -- the Next.js starter project (TypeScript + React)

Monorepo is explicitly avoided because the two projects have different deployment targets, different CI pipelines, and different license requirements.

### Contribution Guidelines

- CONTRIBUTING.md with clear instructions
- Code of Conduct (Contributor Covenant)
- Issue templates (bug report, feature request, template submission)
- PR template (description, testing checklist, screenshots)
- Branch strategy: `main` (stable), `develop` (next release), feature branches
- Required: all PRs pass CI, have at least one review, and include tests for new features
- Documentation updates required with any user-facing change

### Documentation Plan

**Developer Docs (Docusaurus):**
- Architecture overview
- Module development guide (how to create custom modules)
- Custom component development (how to create new section types)
- Hook/filter reference
- REST API reference (auto-generated from endpoint definitions)
- Theme development guide
- Template creation guide

**User Docs (within the same Docusaurus site):**
- Getting started (install plugin + clone starter)
- Template import guide
- Page builder tutorial
- Form builder tutorial
- Theme customization guide
- SEO setup guide
- Deployment guide (Vercel, Netlify, self-hosted)

**Video Tutorials:**
- Quick start (10 min)
- Building a restaurant website (20 min)
- Creating custom components (15 min)
- Deploying to production (10 min)

### Community Building

- GitHub Discussions for questions and ideas
- Discord server for real-time community support
- Monthly "Template of the Month" community challenge
- Contributor recognition (hall of fame in docs)
- Plugin showcase (gallery of sites built with NextPress Builder)

---

## 18. File/Directory Structure

### WordPress Plugin Directory Tree

```
nextpress-builder/
  |-- nextpress-builder.php                    # Main plugin file (bootstrap)
  |-- composer.json                            # PHP dependencies + PSR-4 autoloading
  |-- composer.lock
  |-- package.json                             # JS dependencies for admin UI
  |-- package-lock.json
  |-- webpack.config.js                        # Extends @wordpress/scripts if needed
  |-- phpunit.xml                              # PHPUnit configuration
  |-- phpcs.xml                                # PHPCS configuration
  |-- .phpstan.neon                            # PHPStan configuration
  |-- README.md
  |-- LICENSE                                  # GPLv2
  |-- CHANGELOG.md
  |-- CONTRIBUTING.md
  |
  |-- src/                                     # PHP source (PSR-4: NextPressBuilder\)
  |   |-- Plugin.php                           # Main plugin class
  |   |-- Activator.php                        # Activation hooks (create tables, roles)
  |   |-- Deactivator.php                      # Deactivation hooks (cleanup)
  |   |-- Uninstaller.php                      # Uninstall hooks (remove data)
  |   |
  |   |-- Core/
  |   |   |-- Container.php                    # Dependency injection container
  |   |   |-- ModuleManager.php                # Module discovery and lifecycle
  |   |   |-- ModuleInterface.php              # Module contract
  |   |   |-- AbstractModule.php               # Base module with common functionality
  |   |   |-- RestApiManager.php               # REST route registration
  |   |   |-- DatabaseManager.php              # Migration runner
  |   |   |-- AssetManager.php                 # Admin JS/CSS enqueue
  |   |   |-- HookManager.php                  # Centralized hook registration
  |   |   |-- SettingsManager.php              # Plugin settings
  |   |   |-- WebhookManager.php               # Outgoing webhook delivery
  |   |   |-- Sanitizer.php                    # Input sanitization
  |   |   |-- Validator.php                    # Input validation
  |   |   |-- Capability.php                   # Custom capabilities
  |   |   |
  |   |   |-- Repository/
  |   |   |   |-- AbstractRepository.php       # Base CRUD repository
  |   |   |   |-- PageRepository.php
  |   |   |   |-- SectionRepository.php
  |   |   |   |-- ThemeRepository.php
  |   |   |   |-- FormRepository.php
  |   |   |   |-- SubmissionRepository.php
  |   |   |   |-- ComponentRepository.php
  |   |   |   |-- VariantRepository.php
  |   |   |   |-- ButtonRepository.php
  |   |   |   |-- NavigationRepository.php
  |   |   |   |-- TemplateRepository.php
  |   |   |
  |   |   |-- Migration/
  |   |   |   |-- MigrationInterface.php
  |   |   |   |-- AbstractMigration.php
  |   |   |   |-- MigrationRunner.php
  |   |   |   |-- migrations/
  |   |   |       |-- 001_create_pages_table.php
  |   |   |       |-- 002_create_sections_table.php
  |   |   |       |-- 003_create_components_table.php
  |   |   |       |-- 004_create_style_variants_table.php
  |   |   |       |-- 005_create_forms_table.php
  |   |   |       |-- 006_create_form_submissions_table.php
  |   |   |       |-- 007_create_themes_table.php
  |   |   |       |-- 008_create_buttons_table.php
  |   |   |       |-- 009_create_navigation_menus_table.php
  |   |   |       |-- 010_create_templates_table.php
  |   |   |
  |   |   |-- Rest/
  |   |       |-- AbstractController.php       # Base REST controller
  |   |       |-- Middleware/
  |   |           |-- RateLimiter.php
  |   |           |-- CacheHeaders.php
  |   |           |-- Authentication.php
  |   |
  |   |-- Modules/
  |   |   |-- PageBuilder/
  |   |   |   |-- Module.php                   # Implements ModuleInterface
  |   |   |   |-- Controller/
  |   |   |   |   |-- PageController.php       # REST endpoints for pages
  |   |   |   |   |-- SectionController.php    # REST endpoints for sections
  |   |   |   |-- Service/
  |   |   |   |   |-- PageService.php          # Business logic
  |   |   |   |   |-- SectionService.php
  |   |   |   |   |-- PreviewService.php
  |   |   |   |   |-- ImportExportService.php
  |   |   |   |-- Admin/
  |   |   |       |-- PageBuilderAdmin.php     # Admin page registration
  |   |   |
  |   |   |-- HeaderBuilder/
  |   |   |   |-- Module.php
  |   |   |   |-- Controller/
  |   |   |   |   |-- HeaderController.php
  |   |   |   |-- Service/
  |   |   |       |-- HeaderService.php
  |   |   |
  |   |   |-- FooterBuilder/
  |   |   |   |-- Module.php
  |   |   |   |-- Controller/
  |   |   |   |   |-- FooterController.php
  |   |   |   |-- Service/
  |   |   |       |-- FooterService.php
  |   |   |
  |   |   |-- FormBuilder/
  |   |   |   |-- Module.php
  |   |   |   |-- Controller/
  |   |   |   |   |-- FormController.php
  |   |   |   |   |-- SubmissionController.php
  |   |   |   |-- Service/
  |   |   |   |   |-- FormService.php
  |   |   |   |   |-- SubmissionService.php
  |   |   |   |   |-- NotificationService.php
  |   |   |   |   |-- SpamProtectionService.php
  |   |   |   |   |-- FileUploadService.php
  |   |   |   |-- Integration/
  |   |   |       |-- MailchimpIntegration.php
  |   |   |       |-- WebhookIntegration.php
  |   |   |
  |   |   |-- SeoManager/
  |   |   |   |-- Module.php
  |   |   |   |-- Controller/
  |   |   |   |   |-- SeoController.php
  |   |   |   |   |-- RedirectController.php
  |   |   |   |-- Service/
  |   |   |       |-- SeoService.php
  |   |   |       |-- SitemapService.php
  |   |   |       |-- RedirectService.php
  |   |   |
  |   |   |-- SchemaManager/
  |   |   |   |-- Module.php
  |   |   |   |-- Controller/
  |   |   |   |   |-- SchemaController.php
  |   |   |   |-- Service/
  |   |   |   |   |-- SchemaService.php
  |   |   |   |   |-- SchemaGenerator.php
  |   |   |   |-- Generator/
  |   |   |       |-- LocalBusinessSchema.php
  |   |   |       |-- RestaurantSchema.php
  |   |   |       |-- LegalServiceSchema.php
  |   |   |       |-- ... (one per business type)
  |   |   |       |-- FAQSchema.php
  |   |   |       |-- BreadcrumbSchema.php
  |   |   |       |-- ServiceSchema.php
  |   |   |
  |   |   |-- ThemeManager/
  |   |   |   |-- Module.php
  |   |   |   |-- Controller/
  |   |   |   |   |-- ThemeController.php
  |   |   |   |-- Service/
  |   |   |       |-- ThemeService.php
  |   |   |       |-- CssVariableGenerator.php
  |   |   |       |-- ColorUtility.php
  |   |   |
  |   |   |-- MediaManager/
  |   |   |   |-- Module.php
  |   |   |   |-- Controller/
  |   |   |   |   |-- MediaController.php
  |   |   |   |-- Service/
  |   |   |       |-- MediaService.php
  |   |   |       |-- FocalPointService.php
  |   |   |
  |   |   |-- NavigationManager/
  |   |   |   |-- Module.php
  |   |   |   |-- Controller/
  |   |   |   |   |-- NavigationController.php
  |   |   |   |-- Service/
  |   |   |       |-- NavigationService.php
  |   |   |
  |   |   |-- ButtonManager/
  |   |   |   |-- Module.php
  |   |   |   |-- Controller/
  |   |   |   |   |-- ButtonController.php
  |   |   |   |-- Service/
  |   |   |       |-- ButtonService.php
  |   |   |
  |   |   |-- ComponentLibrary/
  |   |   |   |-- Module.php
  |   |   |   |-- Controller/
  |   |   |   |   |-- ComponentController.php
  |   |   |   |   |-- VariantController.php
  |   |   |   |-- Service/
  |   |   |       |-- ComponentService.php
  |   |   |       |-- VariantService.php
  |   |   |
  |   |   |-- TemplateLibrary/
  |   |   |   |-- Module.php
  |   |   |   |-- Controller/
  |   |   |   |   |-- TemplateController.php
  |   |   |   |-- Service/
  |   |   |   |   |-- TemplateService.php
  |   |   |   |   |-- TemplateImportService.php
  |   |   |   |   |-- TemplateExportService.php
  |   |   |   |-- Fixtures/
  |   |   |       |-- templates/
  |   |   |           |-- restaurant.json
  |   |   |           |-- plumber.json
  |   |   |           |-- lawyer.json
  |   |   |           |-- ... (20 template JSON files)
  |   |   |
  |   |   |-- SecurityManager/
  |   |   |   |-- Module.php
  |   |   |   |-- Service/
  |   |   |       |-- RateLimitService.php
  |   |   |       |-- AuditLogService.php
  |   |   |       |-- ApiKeyService.php
  |   |   |
  |   |   |-- PerformanceManager/
  |   |   |   |-- Module.php
  |   |   |   |-- Service/
  |   |   |       |-- CacheService.php
  |   |   |       |-- ResponseOptimizer.php
  |   |   |
  |   |   |-- AnalyticsIntegration/
  |   |   |   |-- Module.php
  |   |   |   |-- Controller/
  |   |   |   |   |-- AnalyticsController.php
  |   |   |   |-- Service/
  |   |   |       |-- AnalyticsService.php
  |   |   |
  |   |   |-- RevalidationManager/
  |   |       |-- Module.php
  |   |       |-- Controller/
  |   |       |   |-- RevalidationController.php
  |   |       |-- Service/
  |   |           |-- RevalidationService.php
  |   |           |-- PathResolver.php
  |   |           |-- WebhookDelivery.php
  |   |
  |   |-- Admin/                               # Shared admin functionality
  |       |-- AdminPage.php                    # Base admin page class
  |       |-- AdminMenu.php                    # Top-level menu registration
  |       |-- AdminNotices.php                 # Admin notice manager
  |
  |-- admin/                                   # React admin UI source
  |   |-- src/
  |   |   |-- index.tsx                        # Entry point
  |   |   |-- App.tsx                          # Router for admin pages
  |   |   |-- pages/
  |   |   |   |-- Dashboard.tsx
  |   |   |   |-- PageList.tsx
  |   |   |   |-- PageBuilder.tsx              # The main drag-and-drop builder
  |   |   |   |-- HeaderBuilder.tsx
  |   |   |   |-- FooterBuilder.tsx
  |   |   |   |-- FormList.tsx
  |   |   |   |-- FormBuilder.tsx
  |   |   |   |-- FormSubmissions.tsx
  |   |   |   |-- ComponentLibrary.tsx
  |   |   |   |-- TemplateLibrary.tsx
  |   |   |   |-- ThemeEditor.tsx
  |   |   |   |-- NavigationEditor.tsx
  |   |   |   |-- SeoSettings.tsx
  |   |   |   |-- Settings.tsx
  |   |   |-- components/
  |   |   |   |-- builder/
  |   |   |   |   |-- Canvas.tsx               # Drag-and-drop canvas
  |   |   |   |   |-- ComponentPalette.tsx     # Left panel: available components
  |   |   |   |   |-- SectionEditor.tsx        # Right panel: content/style editing
  |   |   |   |   |-- VariantPicker.tsx        # Variant thumbnail grid
  |   |   |   |   |-- StyleControls.tsx        # Color picker, spacing, typography controls
  |   |   |   |   |-- ResponsiveToggle.tsx     # Breakpoint switcher
  |   |   |   |   |-- PreviewIframe.tsx        # Live preview
  |   |   |   |   |-- SectionItem.tsx          # Sortable section in canvas
  |   |   |   |-- forms/
  |   |   |   |   |-- FieldEditor.tsx          # Edit individual form field
  |   |   |   |   |-- FieldList.tsx            # Drag-and-drop field ordering
  |   |   |   |   |-- ConditionalLogicEditor.tsx
  |   |   |   |   |-- MultiStepEditor.tsx
  |   |   |   |   |-- NotificationEditor.tsx
  |   |   |   |-- theme/
  |   |   |   |   |-- ColorPalette.tsx
  |   |   |   |   |-- FontPicker.tsx
  |   |   |   |   |-- SpacingEditor.tsx
  |   |   |   |   |-- ButtonPresetEditor.tsx
  |   |   |   |-- shared/
  |   |   |       |-- MediaPicker.tsx          # WordPress media library integration
  |   |   |       |-- IconPicker.tsx           # Lucide icon selector
  |   |   |       |-- JsonEditor.tsx           # Raw JSON editor for advanced users
  |   |   |       |-- Confirm.tsx              # Confirmation dialog
  |   |   |       |-- Loader.tsx
  |   |   |       |-- Toast.tsx
  |   |   |-- hooks/
  |   |   |   |-- useApi.ts                    # WP REST API wrapper
  |   |   |   |-- useBuilder.ts                # Builder state management
  |   |   |   |-- useUndoRedo.ts               # Undo/redo state
  |   |   |-- store/
  |   |   |   |-- builderStore.ts              # Zustand store for builder state
  |   |   |   |-- themeStore.ts
  |   |   |-- utils/
  |   |   |   |-- api.ts
  |   |   |   |-- validators.ts
  |   |   |-- styles/
  |   |       |-- admin.css                    # Custom admin styles
  |   |-- tsconfig.json
  |
  |-- assets/                                  # Static assets
  |   |-- images/
  |   |   |-- templates/                       # Template preview images
  |   |   |-- variants/                        # Variant preview images
  |   |   |-- admin/                           # Admin UI images
  |   |-- fixtures/
  |       |-- default-components.json          # Built-in component definitions
  |       |-- default-variants.json            # Built-in style variants
  |       |-- default-buttons.json             # Default button presets
  |       |-- default-themes.json              # Pre-built themes
  |
  |-- languages/                               # i18n .pot/.po/.mo files
  |   |-- nextpress-builder.pot
  |
  |-- tests/                                   # PHPUnit tests
      |-- bootstrap.php
      |-- Unit/
      |   |-- Core/
      |   |   |-- ContainerTest.php
      |   |   |-- ModuleManagerTest.php
      |   |   |-- SanitizerTest.php
      |   |-- Modules/
      |       |-- PageBuilder/
      |       |   |-- PageServiceTest.php
      |       |-- FormBuilder/
      |       |   |-- FormServiceTest.php
      |       |   |-- SpamProtectionTest.php
      |       |-- ThemeManager/
      |           |-- CssVariableGeneratorTest.php
      |-- Integration/
          |-- RestApi/
              |-- PageEndpointTest.php
              |-- ThemeEndpointTest.php
```

### Next.js Project Directory Tree

```
nextpress-starter/
  |-- package.json
  |-- package-lock.json
  |-- next.config.ts
  |-- tsconfig.json
  |-- tailwind.config.ts                       # References CSS variables for theme integration
  |-- postcss.config.mjs
  |-- next-sitemap.config.js
  |-- next-env.d.ts
  |-- eslint.config.mjs
  |-- .env.example                             # Template environment variables
  |-- .env.local                               # Local environment (gitignored)
  |-- README.md
  |-- LICENSE                                  # MIT
  |-- CHANGELOG.md
  |
  |-- public/
  |   |-- images/
  |   |   |-- placeholder.svg                  # Placeholder for missing images
  |   |-- robots.txt                           # Fallback (dynamic robots.ts preferred)
  |
  |-- src/
  |   |-- app/
  |   |   |-- layout.tsx                       # Root layout: theme, nav, header, footer
  |   |   |-- page.tsx                         # Home page (slug: "home")
  |   |   |-- [...slug]/
  |   |   |   |-- page.tsx                     # Catch-all dynamic page renderer
  |   |   |-- not-found.tsx
  |   |   |-- error.tsx
  |   |   |-- loading.tsx
  |   |   |-- sitemap.ts                       # Dynamic sitemap from WP API
  |   |   |-- robots.ts                        # Dynamic robots from WP API
  |   |   |-- globals.css                      # Tailwind base + custom utilities
  |   |   |-- api/
  |   |       |-- revalidate/
  |   |       |   |-- route.ts                 # On-demand revalidation endpoint
  |   |       |-- preview/
  |   |       |   |-- route.ts                 # Builder preview endpoint
  |   |       |-- forms/
  |   |       |   |-- [slug]/
  |   |       |       |-- submit/
  |   |       |           |-- route.ts         # Form submission proxy
  |   |       |-- upload/
  |   |           |-- route.ts                 # File upload handler
  |   |
  |   |-- components/
  |   |   |-- SectionRenderer.tsx              # Maps sections to components + renders
  |   |   |-- registry/
  |   |   |   |-- ComponentRegistry.ts         # Section type -> React component map
  |   |   |   |-- index.ts
  |   |   |-- sections/
  |   |   |   |-- Hero/
  |   |   |   |   |-- Hero.tsx                 # Main component (variant switcher)
  |   |   |   |   |-- Hero.types.ts            # TypeScript interfaces
  |   |   |   |   |-- variants/
  |   |   |   |   |   |-- HeroVariant01.tsx    # Full-screen centered
  |   |   |   |   |   |-- HeroVariant02.tsx    # Full-screen centered + form
  |   |   |   |   |   |-- HeroVariant03.tsx    # Split: text left, image right
  |   |   |   |   |   |-- ... (up to 20)
  |   |   |   |   |-- index.ts
  |   |   |   |-- ServicesGrid/
  |   |   |   |   |-- ServicesGrid.tsx
  |   |   |   |   |-- ServicesGrid.types.ts
  |   |   |   |   |-- variants/
  |   |   |   |   |   |-- ServicesGridVariant01.tsx
  |   |   |   |   |   |-- ...
  |   |   |   |   |-- index.ts
  |   |   |   |-- Testimonials/
  |   |   |   |   |-- ...
  |   |   |   |-- CTABanner/
  |   |   |   |   |-- ...
  |   |   |   |-- ContactForm/
  |   |   |   |   |-- ...
  |   |   |   |-- FAQ/
  |   |   |   |   |-- ...
  |   |   |   |-- Pricing/
  |   |   |   |   |-- ...
  |   |   |   |-- Team/
  |   |   |   |   |-- ...
  |   |   |   |-- Gallery/
  |   |   |   |   |-- ...
  |   |   |   |-- StatsCounter/
  |   |   |   |   |-- ...
  |   |   |   |-- Timeline/
  |   |   |   |   |-- ...
  |   |   |   |-- BeforeAfter/
  |   |   |   |   |-- ...
  |   |   |   |-- ZoneIntervention/
  |   |   |   |   |-- ...
  |   |   |   |-- RichText/
  |   |   |   |   |-- ...
  |   |   |   |-- Newsletter/
  |   |   |   |   |-- ...
  |   |   |   |-- Portfolio/
  |   |   |   |   |-- ...
  |   |   |   |-- About/
  |   |   |   |   |-- ...
  |   |   |   |-- ValuesGrid/
  |   |   |   |   |-- ...
  |   |   |   |-- Map/
  |   |   |   |   |-- ...
  |   |   |   |-- Divider/
  |   |   |   |   |-- ...
  |   |   |   |-- Embed/
  |   |   |       |-- ...
  |   |   |-- layout/
  |   |   |   |-- Header/
  |   |   |   |   |-- Header.tsx               # Main header (switches on header data)
  |   |   |   |   |-- variants/
  |   |   |   |   |   |-- StandardHeader.tsx
  |   |   |   |   |   |-- CenteredHeader.tsx
  |   |   |   |   |   |-- SplitNavHeader.tsx
  |   |   |   |   |   |-- TransparentHeader.tsx
  |   |   |   |   |   |-- MinimalHeader.tsx
  |   |   |   |   |-- MobileMenu.tsx
  |   |   |   |   |-- TopBar.tsx
  |   |   |   |-- Footer/
  |   |   |       |-- Footer.tsx
  |   |   |       |-- variants/
  |   |   |           |-- StandardFooter.tsx
  |   |   |           |-- MinimalFooter.tsx
  |   |   |           |-- MegaFooter.tsx
  |   |   |           |-- CenteredFooter.tsx
  |   |   |           |-- SplitFooter.tsx
  |   |   |-- forms/
  |   |   |   |-- DynamicForm.tsx              # Renders form from config JSON
  |   |   |   |-- FormField.tsx                # Field type switcher
  |   |   |   |-- MultiStepForm.tsx            # Step navigation + progress
  |   |   |   |-- ConditionalField.tsx         # Show/hide logic
  |   |   |   |-- FileUpload.tsx               # File upload with preview
  |   |   |   |-- fields/
  |   |   |       |-- TextField.tsx
  |   |   |       |-- TextareaField.tsx
  |   |   |       |-- SelectField.tsx
  |   |   |       |-- RadioField.tsx
  |   |   |       |-- CheckboxField.tsx
  |   |   |       |-- FileField.tsx
  |   |   |       |-- DateField.tsx
  |   |   |       |-- NumberField.tsx
  |   |   |-- seo/
  |   |   |   |-- JsonLd.tsx                   # Renders JSON-LD from API data
  |   |   |   |-- Breadcrumbs.tsx              # Visual breadcrumbs + schema
  |   |   |-- ui/
  |   |       |-- Button.tsx                   # Dynamic button from preset data
  |   |       |-- SectionWrapper.tsx           # Responsive CSS injection
  |   |       |-- SectionTitle.tsx             # Reusable section heading
  |   |       |-- AnimatedSection.tsx          # Intersection observer + animation
  |   |       |-- Container.tsx                # Max-width container
  |   |       |-- Icon.tsx                     # Lucide icon by name
  |   |       |-- Skeleton.tsx                 # Loading skeleton
  |   |
  |   |-- lib/
  |   |   |-- api/
  |   |   |   |-- client.ts                    # Base fetch wrapper with caching + error handling
  |   |   |   |-- pages.ts                     # getPage(), getPages(), getPageSEO()
  |   |   |   |-- theme.ts                     # getActiveTheme()
  |   |   |   |-- forms.ts                     # getFormConfig(), submitForm()
  |   |   |   |-- seo.ts                       # getGlobalSEO(), getSitemap(), getRedirects()
  |   |   |   |-- schema.ts                    # getPageSchema(), getGlobalSchema()
  |   |   |   |-- navigation.ts                # getNavigation()
  |   |   |   |-- site.ts                      # getSiteConfig()
  |   |   |   |-- headers.ts                   # getHeader()
  |   |   |   |-- footers.ts                   # getFooter()
  |   |   |   |-- analytics.ts                 # getAnalyticsConfig()
  |   |   |-- utils/
  |   |   |   |-- cn.ts                        # clsx + twMerge utility
  |   |   |   |-- responsive.ts                # ResponsiveValue helpers
  |   |   |   |-- sanitize.ts                  # Client-side XSS prevention
  |   |   |   |-- metadata.ts                  # Metadata builder helper
  |   |   |-- hooks/
  |   |   |   |-- useForm.ts                   # Form state, validation, submission
  |   |   |   |-- useAnimation.ts              # Scroll-triggered animations
  |   |   |   |-- useBreakpoint.ts             # Current breakpoint detection
  |   |   |   |-- useMediaQuery.ts             # Generic media query hook
  |   |   |-- types/
  |   |       |-- api.ts                       # All API response interfaces
  |   |       |-- sections.ts                  # Section content/style types
  |   |       |-- theme.ts                     # Theme token types
  |   |       |-- forms.ts                     # Form config/field types
  |   |       |-- navigation.ts                # Nav item types
  |   |
  |   |-- middleware.ts                        # Security headers, redirects, preview mode
  |
  |-- tests/
  |   |-- unit/
  |   |   |-- components/
  |   |   |   |-- SectionRenderer.test.tsx
  |   |   |   |-- DynamicForm.test.tsx
  |   |   |-- lib/
  |   |       |-- api/
  |   |           |-- client.test.ts
  |   |-- e2e/
  |       |-- home.spec.ts
  |       |-- dynamic-page.spec.ts
  |       |-- form-submission.spec.ts
  |       |-- seo.spec.ts
  |
  |-- playwright.config.ts
  |-- jest.config.ts
```

---

## Key Architectural Decisions Summary

1. **Custom tables over WordPress post meta.** Post meta becomes a performance nightmare at scale with complex JSON structures. Custom tables with proper indexes enable fast, complex queries.

2. **JSON columns for flexible schemas.** Section content, styles, form fields, and theme tokens are stored as JSON in MySQL. This provides schema flexibility without constant migrations while still supporting indexed lookups on fixed columns.

3. **Component Registry pattern on the frontend.** A central mapping from string type names to React components allows the WP backend to control page composition without any frontend code changes. New section types are added by registering a new component.

4. **Variant system as separate data.** Style variants are stored independently from section instances. This allows variants to be updated globally (e.g., fixing a layout issue in variant-05 of Hero) without touching individual page data.

5. **Server Components by default.** Every section is a React Server Component unless it requires interactivity (forms, sliders, animations). This minimizes client-side JavaScript and maximizes performance.

6. **Tag-based revalidation over time-based.** Instead of relying on `revalidate: 60` alone, the system uses `next.cache` tags and on-demand revalidation from WordPress webhooks. Pages update within seconds of a WordPress edit, not after a TTL expires.

7. **Two repositories, not a monorepo.** The WordPress plugin and Next.js starter serve different audiences, have different deployment pipelines, and require different licenses. Keeping them separate simplifies both contribution and consumption.

8. **Module system with auto-discovery.** Modules are self-contained and can be enabled/disabled. This allows users to disable features they do not need and allows the community to create third-party modules.

---

### Critical Files for Implementation

- `E:/Headless WP/ll-couverture-frontend/src/components/SectionRenderer.tsx` -- This is the foundational pattern for the NextPress Builder's Component Registry. The section-to-component mapping, the rendering loop, and the SectionWrapper integration are all patterns that will be generalized into the new framework.

- `E:/Headless WP/ll-couverture-frontend/src/lib/wordpress.ts` -- This is the API client pattern that will evolve into the modular API client layer (`lib/api/client.ts` + per-resource modules). The type definitions here (PageSection, SectionStyle, ResponsiveValue, Appearance) are the seed for the full type system.

- `E:/Headless WP/ll-couverture-frontend/src/components/ui/SectionWrapper.tsx` -- The responsive CSS variable injection system is a unique pattern that will be preserved and extended in the new framework. The approach of generating inline `<style>` tags for responsive values is performant and avoids runtime CSS-in-JS.

- `E:/Headless WP/ll-couverture-frontend/wordpress/jjm-revalidate/jjm-revalidate.php` -- This revalidation plugin is the prototype for the RevalidationManager module. Its hook-based approach to detecting content changes and triggering webhooks will be generalized and enhanced with queuing, retry logic, and smart path resolution.

- `E:/Headless WP/ll-couverture-frontend/src/app/layout.tsx` -- The root layout pattern (fetching theme/appearance data, injecting CSS variables, loading fonts, rendering header/footer) is the blueprint for the NextPress Builder's root layout, which will fetch all this data from the new modular API.