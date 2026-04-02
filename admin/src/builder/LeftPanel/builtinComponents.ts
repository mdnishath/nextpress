import type { Component } from '../../types/builder';

/**
 * Built-in components that are always available in the palette.
 * These don't need database records — they're hardcoded in the builder.
 */
export const BUILT_IN_COMPONENTS: Component[] = [
  // ─── Layout ───
  {
    id: 0,
    slug: 'container',
    name: 'Container',
    description: 'Flexbox layout container',
    category: 'structure',
    icon: 'layout',
    content_schema: { fields: [] },
    default_content: {},
    is_container: true,
  },
  {
    id: 0,
    slug: 'grid',
    name: 'Grid',
    description: 'CSS Grid layout container',
    category: 'structure',
    icon: 'grid',
    content_schema: { fields: [] },
    default_content: {},
    is_container: true,
  },

  // ─── Basic ───
  {
    id: 0,
    slug: 'heading',
    name: 'Heading',
    description: 'Heading text H1-H6',
    category: 'basic',
    icon: 'type',
    content_schema: {
      fields: [
        {
          key: 'text',
          label: 'Heading',
          type: 'text',
          default: 'Add Your Heading Text Here',
          placeholder: 'Enter heading...',
        },
        {
          key: 'tag',
          label: 'HTML Tag',
          type: 'select',
          default: 'h2',
          options: [
            { label: 'H1', value: 'h1' },
            { label: 'H2', value: 'h2' },
            { label: 'H3', value: 'h3' },
            { label: 'H4', value: 'h4' },
            { label: 'H5', value: 'h5' },
            { label: 'H6', value: 'h6' },
          ],
        },
        {
          key: 'alignment',
          label: 'Alignment',
          type: 'select',
          default: 'left',
          options: [
            { label: 'Left', value: 'left' },
            { label: 'Center', value: 'center' },
            { label: 'Right', value: 'right' },
          ],
        },
        {
          key: 'link',
          label: 'Link',
          type: 'url',
          default: '',
          placeholder: 'https://...',
        },
      ],
    },
    default_content: {
      text: 'Add Your Heading Text Here',
      tag: 'h2',
      alignment: 'left',
      link: '',
    },
    is_container: false,
  },
];
