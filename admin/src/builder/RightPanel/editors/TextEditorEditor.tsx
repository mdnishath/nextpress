import { useState, useEffect, useRef } from '@wordpress/element';
import { useBuilderStore } from '../../../store/builderStore';
import { TypographySection } from '../controls/SharedStyleControls';
import type { Section } from '../../../types/builder';

/* ─── Content Editor ─── */

export function TextEditorContentEditor({ section }: { section: Section }) {
  const { updateContent } = useBuilderStore();
  const c = section.content as Record<string, string>;
  const [showHtml, setShowHtml] = useState(false);
  const [htmlText, setHtmlText] = useState(c.content || '');
  const editorRef = useRef<HTMLDivElement>(null);
  const initializedRef = useRef(false);

  useEffect(() => {
    if (editorRef.current && !initializedRef.current) {
      editorRef.current.innerHTML = c.content || '<p>Add your text here. Click to edit.</p>';
      initializedRef.current = true;
    }
  }, []);

  useEffect(() => {
    if (showHtml) {
      setHtmlText(c.content || '');
    } else if (editorRef.current) {
      editorRef.current.innerHTML = c.content || '<p>Add your text here. Click to edit.</p>';
    }
  }, [showHtml]);

  const update = (key: string, val: string) => {
    updateContent(section.id, { [key]: val });
  };

  const handleInput = () => {
    if (editorRef.current) {
      update('content', editorRef.current.innerHTML);
    }
  };

  return (
    <div>
      <div className="el-section">
        <div className="el-section__header">
          <span className="el-section__arrow">▼</span>
          <span className="el-section__title">Text Editor</span>
        </div>
        <div className="el-section__body">
          <div style={{ display: 'flex', justifyContent: 'flex-end', marginBottom: 6 }}>
            <button
              onClick={() => setShowHtml(!showHtml)}
              style={{ fontSize: 11, color: '#7c3aed', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 600 }}
            >
              {showHtml ? 'Visual' : 'HTML'}
            </button>
          </div>

          {showHtml ? (
            <textarea
              className="el-textarea"
              rows={8}
              value={htmlText}
              onChange={(e) => { setHtmlText(e.target.value); update('content', e.target.value); }}
              style={{ fontFamily: 'var(--npb-font-mono)', fontSize: 12 }}
            />
          ) : (
            <>
              <div style={{
                display: 'flex', gap: 2, padding: '4px 8px',
                border: '1px solid rgba(255,255,255,0.12)', borderBottom: 'none',
                borderRadius: '4px 4px 0 0', background: 'rgba(255,255,255,0.05)',
              }}>
                {[
                  { cmd: 'bold', label: 'B', style: { fontWeight: 700 } as React.CSSProperties },
                  { cmd: 'italic', label: 'I', style: { fontStyle: 'italic' } as React.CSSProperties },
                  { cmd: 'underline', label: 'U', style: { textDecoration: 'underline' } as React.CSSProperties },
                  { cmd: 'strikeThrough', label: 'S', style: { textDecoration: 'line-through' } as React.CSSProperties },
                ].map((btn) => (
                  <button
                    key={btn.cmd}
                    onMouseDown={(e) => {
                      e.preventDefault();
                      document.execCommand(btn.cmd);
                      setTimeout(() => { if (editorRef.current) update('content', editorRef.current.innerHTML); }, 0);
                    }}
                    style={{
                      width: 28, height: 28, border: 'none', background: 'transparent',
                      cursor: 'pointer', fontSize: 13, borderRadius: 4, color: '#e0e0e0', ...btn.style,
                    }}
                  >
                    {btn.label}
                  </button>
                ))}
              </div>
              <div
                ref={editorRef}
                contentEditable
                onInput={handleInput}
                style={{
                  minHeight: 120, padding: '10px 12px',
                  border: '1px solid rgba(255,255,255,0.12)', borderTop: 'none',
                  borderRadius: '0 0 4px 4px',
                  background: 'rgba(255,255,255,0.05)', color: '#e0e0e0',
                  lineHeight: 1.6, outline: 'none', fontSize: 13,
                  wordWrap: 'break-word', whiteSpace: 'pre-wrap',
                }}
                suppressContentEditableWarning
              />
            </>
          )}
        </div>
      </div>
    </div>
  );
}

/* ─── Style Editor — just reuse shared TypographySection ─── */

export function TextEditorStyleEditor({ section }: { section: Section }) {
  return <TypographySection section={section} title="Text Editor" />;
}
