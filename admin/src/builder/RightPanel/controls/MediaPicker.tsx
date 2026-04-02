import { useCallback } from '@wordpress/element';
import { Image, X } from 'lucide-react';

interface MediaPickerProps {
  value: string;
  onChange: (url: string) => void;
  label?: string;
}

/**
 * WordPress Media Library picker.
 * Opens the native WP media modal to select an image.
 */
export function MediaPicker({ value, onChange, label }: MediaPickerProps) {
  const openMediaLibrary = useCallback(() => {
    // wp.media is available in WP admin context
    const wpMedia = (window as any).wp?.media;
    if (!wpMedia) {
      // Fallback: prompt for URL
      const url = prompt('Enter image URL:', value || '');
      if (url !== null) onChange(url);
      return;
    }

    const frame = wpMedia({
      title: label || 'Select Image',
      multiple: false,
      library: { type: 'image' },
      button: { text: 'Use this image' },
    });

    frame.on('select', () => {
      const attachment = frame.state().get('selection').first().toJSON();
      onChange(attachment.url);
    });

    frame.open();
  }, [value, onChange, label]);

  return (
    <div className="el-control">
      {label && <span className="el-control__label">{label}</span>}

      {value ? (
        /* Image preview */
        <div className="el-media-preview">
          <img src={value} alt="" className="el-media-preview__img" />
          <div className="el-media-preview__actions">
            <button
              type="button"
              className="el-media-preview__btn"
              onClick={openMediaLibrary}
              title="Change image"
            >
              <Image size={14} />
            </button>
            <button
              type="button"
              className="el-media-preview__btn el-media-preview__btn--remove"
              onClick={() => onChange('')}
              title="Remove image"
            >
              <X size={14} />
            </button>
          </div>
        </div>
      ) : (
        /* Upload button */
        <button
          type="button"
          className="el-media-upload"
          onClick={openMediaLibrary}
        >
          <Image size={20} />
          <span>Choose Image</span>
        </button>
      )}
    </div>
  );
}
