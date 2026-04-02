import { useBuilderStore } from '../../../store/builderStore';
import { TypographySection } from '../controls/SharedStyleControls';
import type { Section } from '../../../types/builder';

const HTML_TAGS = [
  { label: 'H1', value: 'h1' },
  { label: 'H2', value: 'h2' },
  { label: 'H3', value: 'h3' },
  { label: 'H4', value: 'h4' },
  { label: 'H5', value: 'h5' },
  { label: 'H6', value: 'h6' },
];

export function HeadingContentEditor({ section }: { section: Section }) {
  const { updateContent } = useBuilderStore();
  const c = section.content as Record<string, string>;

  const update = (key: string, val: string) => {
    updateContent(section.id, { [key]: val });
  };

  return (
    <div>
      <div className="el-section">
        <div className="el-section__header">
          <span className="el-section__arrow">▼</span>
          <span className="el-section__title">Heading</span>
        </div>
        <div className="el-section__body">
          <div className="el-control">
            <span className="el-control__label">Title</span>
            <textarea
              className="el-textarea"
              rows={3}
              value={c.text || 'Add Your Heading Text Here'}
              onChange={(e) => update('text', e.target.value)}
              placeholder="Add Your Heading Text Here"
            />
          </div>
          <div className="el-control">
            <span className="el-control__label">Link</span>
            <input
              className="el-input el-input--full"
              type="url"
              value={c.link || ''}
              onChange={(e) => update('link', e.target.value)}
              placeholder="Type or paste your URL"
            />
          </div>
          <div className="el-control el-control--row">
            <span className="el-control__label">HTML Tag</span>
            <select
              className="el-select"
              value={c.tag || 'h2'}
              onChange={(e) => update('tag', e.target.value)}
            >
              {HTML_TAGS.map((t) => (
                <option key={t.value} value={t.value}>{t.label}</option>
              ))}
            </select>
          </div>
        </div>
      </div>
    </div>
  );
}

export function HeadingStyleEditor({ section }: { section: Section }) {
  return <TypographySection section={section} title="Heading" />;
}
