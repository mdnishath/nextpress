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

  // ─── Text Editor ───
  {
    id: 0,
    slug: 'text_editor',
    name: 'Text Editor',
    description: 'Rich text with formatting',
    category: 'basic',
    icon: 'align-left',
    content_schema: {
      fields: [
        {
          key: 'content',
          label: 'Content',
          type: 'richtext',
          default: '<p>Add your text here. Click to edit.</p>',
        },
      ],
    },
    default_content: {
      content: '<p>Add your text here. Click to edit.</p>',
    },
    is_container: false,
  },

  // ─── Image ───
  {
    id: 0,
    slug: 'image',
    name: 'Image',
    description: 'Single image with caption',
    category: 'basic',
    icon: 'image',
    content_schema: {
      fields: [
        { key: 'src', label: 'Image', type: 'image', default: '' },
        { key: 'alt', label: 'Alt Text', type: 'text', default: '', placeholder: 'Describe the image...' },
        { key: 'caption', label: 'Caption', type: 'text', default: '', placeholder: 'Image caption' },
        { key: 'link', label: 'Link', type: 'url', default: '', placeholder: 'https://...' },
        {
          key: 'alignment',
          label: 'Alignment',
          type: 'select',
          default: 'center',
          options: [
            { label: 'Left', value: 'left' },
            { label: 'Center', value: 'center' },
            { label: 'Right', value: 'right' },
          ],
        },
      ],
    },
    default_content: {
      src: '',
      alt: '',
      caption: '',
      link: '',
      alignment: 'center',
    },
    is_container: false,
  },

  // ─── Button ───
  {
    id: 0,
    slug: 'button',
    name: 'Button',
    description: 'Call-to-action button',
    category: 'basic',
    icon: 'mouse-pointer',
    content_schema: {
      fields: [
        { key: 'text', label: 'Text', type: 'text', default: 'Click Here', placeholder: 'Button text' },
        { key: 'link', label: 'Link', type: 'url', default: '#', placeholder: 'https://...' },
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
          key: 'size',
          label: 'Size',
          type: 'select',
          default: 'medium',
          options: [
            { label: 'Small', value: 'small' },
            { label: 'Medium', value: 'medium' },
            { label: 'Large', value: 'large' },
          ],
        },
      ],
    },
    default_content: {
      text: 'Click Here',
      link: '#',
      alignment: 'left',
      size: 'medium',
    },
    is_container: false,
  },

  // ─── Spacer ───
  {
    id: 0,
    slug: 'spacer',
    name: 'Spacer',
    description: 'Empty space between elements',
    category: 'basic',
    icon: 'minus',
    content_schema: {
      fields: [
        { key: 'height', label: 'Height (px)', type: 'number', default: 40, min: 1, max: 500 },
      ],
    },
    default_content: {
      height: 40,
    },
    is_container: false,
  },
];
