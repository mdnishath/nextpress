import { useState } from '@wordpress/element';
import { Plus, Folder, Sparkles, X, ArrowLeft } from 'lucide-react';
import { useBuilderStore } from '../../store/builderStore';
import { generateSectionId } from '../../utils/helpers';
import { getDefaultStyle } from '../../utils/constants';
import type { Section } from '../../types/builder';

type Step = 'idle' | 'choose-layout' | 'choose-structure';

const STRUCTURE_PRESETS = [
  { id: '1col',   cols: 1, visual: [1] },
  { id: '2eq',    cols: 2, visual: [1, 1] },
  { id: '2-l',    cols: 2, visual: [2, 1] },
  { id: '2-r',    cols: 2, visual: [1, 2] },
  { id: '3eq',    cols: 3, visual: [1, 1, 1] },
  { id: '3-l',    cols: 3, visual: [2, 1, 1] },
  { id: '3-r',    cols: 3, visual: [1, 1, 2] },
  { id: '3-m',    cols: 3, visual: [1, 2, 1] },
  { id: '4eq',    cols: 4, visual: [1, 1, 1, 1] },
  { id: '2-13',   cols: 2, visual: [1, 2] },
  { id: '2-14',   cols: 2, visual: [1, 3] },
  { id: '2-34',   cols: 2, visual: [3, 1] },
];

export function EmptyCanvas() {
  const [step, setStep] = useState<Step>('idle');
  const { addSection, pageId } = useBuilderStore();

  const createContainer = (layoutType: 'flex' | 'grid', presetVisual?: number[]) => {
    if (!pageId) return;

    const containerId = generateSectionId();
    const container: Section = {
      id: containerId,
      page_id: pageId,
      parent_id: null,
      section_type: 'container',
      variant_id: '',
      content: {},
      style: getDefaultStyle('container'),
      layout: {
        type: 'flex',
        direction: presetVisual && presetVisual.length > 1 ? 'row' : 'column',
      },
      sort_order: 0,
      is_visible: true,
      custom_css: '',
      custom_id: '',
    };

    addSection(container, null);

    // Create child containers for preset
    if (presetVisual && presetVisual.length > 1) {
      presetVisual.forEach((fr, i) => {
        addSection({
          id: generateSectionId(),
          page_id: pageId,
          parent_id: containerId,
          section_type: 'container',
          variant_id: '',
          content: {},
          style: {
            flexSize: 'custom',
            flexGrow: String(fr),
            flexShrink: '1',
            flexBasis: '0%',
          },
          layout: { type: 'flex', direction: 'column' },
          sort_order: i,
          is_visible: true,
          custom_css: '',
          custom_id: '',
        }, containerId);
      });
    }

    setStep('idle');
  };

  // ─── Idle: 3 circle buttons ───
  if (step === 'idle') {
    return (
      <div className="npb-empty-drop">
        <div className="npb-empty-drop__inner">
          <div className="npb-empty-drop__buttons">
            <button type="button" className="npb-empty-drop__btn" title="Add new container" onClick={() => setStep('choose-layout')}>
              <Plus size={20} />
            </button>
            <button type="button" className="npb-empty-drop__btn npb-empty-drop__btn--dark" title="Choose a template">
              <Folder size={18} />
            </button>
            <button type="button" className="npb-empty-drop__btn npb-empty-drop__btn--accent" title="Generate with AI">
              <Sparkles size={18} />
            </button>
          </div>
          <p className="npb-empty-drop__text">Drag widget here</p>
        </div>
      </div>
    );
  }

  // ─── Step 1: Choose layout type ───
  if (step === 'choose-layout') {
    return (
      <div className="npb-empty-drop">
        <div className="npb-empty-drop__inner">
          <button type="button" className="npb-chooser__close" onClick={() => setStep('idle')}>
            <X size={20} />
          </button>
          <p className="npb-chooser__title">Which layout would you like to use?</p>
          <div className="npb-chooser__options">
            {/* Flexbox */}
            <button type="button" className="npb-chooser__option" onClick={() => setStep('choose-structure')}>
              <div className="npb-chooser__thumb npb-chooser__thumb--flexbox">
                <div /><div />
                <div /><div />
              </div>
              <span>Flexbox</span>
            </button>
            {/* Grid */}
            <button type="button" className="npb-chooser__option" onClick={() => createContainer('grid')}>
              <div className="npb-chooser__thumb npb-chooser__thumb--grid">
                <div /><div />
                <div /><div />
              </div>
              <span>Grid</span>
            </button>
          </div>
        </div>
      </div>
    );
  }

  // ─── Step 2: Choose structure preset ───
  return (
    <div className="npb-empty-drop">
      <div className="npb-empty-drop__inner">
        <div className="npb-chooser__top-bar">
          <button type="button" className="npb-chooser__back" onClick={() => setStep('choose-layout')}>
            <ArrowLeft size={16} />
          </button>
          <button type="button" className="npb-chooser__close" onClick={() => setStep('idle')}>
            <X size={20} />
          </button>
        </div>
        <p className="npb-chooser__title">Select your Structure</p>
        <div className="npb-chooser__presets">
          {STRUCTURE_PRESETS.map((preset) => (
            <button
              key={preset.id}
              type="button"
              className="npb-chooser__preset"
              onClick={() => createContainer('flex', preset.visual)}
              title={`${preset.cols} column${preset.cols > 1 ? 's' : ''}`}
            >
              {preset.visual.map((fr, i) => (
                <div key={i} className="npb-chooser__preset-col" style={{ flex: fr }} />
              ))}
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}
