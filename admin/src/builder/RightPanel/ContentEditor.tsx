import { useBuilderStore } from '../../store/builderStore';
import { useComponents } from '../../api/useComponents';
import type { Section, ContentField } from '../../types/builder';

// Field components
import { TextField } from './fields/TextField';
import { TextareaField } from './fields/TextareaField';
import { RichtextField } from './fields/RichtextField';
import { NumberField } from './fields/NumberField';
import { RangeField } from './fields/RangeField';
import { SelectField } from './fields/SelectField';
import { BooleanField } from './fields/BooleanField';
import { ColorField } from './fields/ColorField';
import { ImageField } from './fields/ImageField';
import { IconField } from './fields/IconField';
import { UrlField } from './fields/UrlField';
import { RepeaterField } from './fields/RepeaterField';
import { ButtonPresetField } from './fields/ButtonPresetField';
import { FormSelectField } from './fields/FormSelectField';
import { ResponsiveField } from './fields/ResponsiveField';

interface ContentEditorProps {
  section: Section;
}

export function ContentEditor({ section }: ContentEditorProps) {
  const { updateContent } = useBuilderStore();
  const { components } = useComponents();

  // Match by slug — normalize underscores/hyphens for comparison
  const normalize = (s: string) => s.toLowerCase().replace(/[_-]/g, '');
  const sectionSlug = normalize(section.section_type);
  const component = components.find(
    (c) => c.slug === section.section_type || normalize(c.slug) === sectionSlug,
  );
  let schema = component?.content_schema;
  if (typeof schema === 'string') {
    try { schema = JSON.parse(schema); } catch { schema = { fields: [] }; }
  }
  const fields = Array.isArray(schema?.fields) ? schema.fields : [];

  // Container content editing is handled by ContainerContentEditor via RightPanel
  if (section.section_type === 'container') {
    return null;
  }

  if (fields.length === 0) {
    return (
      <div style={{ color: '#a1a1aa', fontSize: 13, padding: 8 }}>
        {!component ? (
          <div>Loading component fields...</div>
        ) : (
          <div>No editable content fields for this component.</div>
        )}
      </div>
    );
  }

  const handleChange = (key: string, value: unknown) => {
    updateContent(section.id, { [key]: value });
  };

  return (
    <div className="el-section">
      <div className="el-section__header">
        <span className="el-section__arrow">▼</span>
        <span className="el-section__title">Content</span>
      </div>
      <div className="el-section__body">
        {fields.map((field: ContentField) => (
          <FieldRenderer
            key={field.key}
            field={field}
            value={section.content[field.key]}
            onChange={(value) => handleChange(field.key, value)}
          />
        ))}
      </div>
    </div>
  );
}

interface FieldRendererProps {
  field: ContentField;
  value: unknown;
  onChange: (value: unknown) => void;
}

function FieldRenderer({ field, value, onChange }: FieldRendererProps) {
  switch (field.type) {
    case 'text':
      return <TextField field={field} value={value} onChange={onChange} />;
    case 'textarea':
      return <TextareaField field={field} value={value} onChange={onChange} />;
    case 'richtext':
      return <RichtextField field={field} value={value} onChange={onChange} />;
    case 'number':
      return <NumberField field={field} value={value} onChange={onChange} />;
    case 'range':
      return <RangeField field={field} value={value} onChange={onChange} />;
    case 'select':
      return <SelectField field={field} value={value} onChange={onChange} />;
    case 'boolean':
      return <BooleanField field={field} value={value} onChange={onChange} />;
    case 'color':
      return <ColorField field={field} value={value} onChange={onChange} />;
    case 'image':
      return <ImageField field={field} value={value} onChange={onChange} />;
    case 'icon':
      return <IconField field={field} value={value} onChange={onChange} />;
    case 'url':
      return <UrlField field={field} value={value} onChange={onChange} />;
    case 'repeater':
      return <RepeaterField field={field} value={value} onChange={onChange} />;
    case 'button_preset':
      return <ButtonPresetField field={field} value={value} onChange={onChange} />;
    case 'form_select':
      return <FormSelectField field={field} value={value} onChange={onChange} />;
    case 'responsive':
      return <ResponsiveField field={field} value={value} onChange={onChange} />;
    default:
      return <TextField field={field} value={value} onChange={onChange} />;
  }
}
