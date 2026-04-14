/**
 * ImageField Component
 *
 * Image upload field with WordPress media library integration.
 *
 * @package HyperFields
 */

import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, Flex, FlexItem } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

/**
 * ImageField Component
 *
 * @param {Object} props - Component props
 * @param {string} props.name - Field name
 * @param {string} props.label - Field label
 * @param {number} props.value - Attachment ID
 * @param {Function} props.onChange - Change callback
 * @param {boolean} props.required - Whether field is required
 * @param {string} props.help - Help text
 * @param {string} props.buttonLabel - Upload button label
 * @param {number} props.maxWidth - Maximum width for preview
 * @param {number} props.maxHeight - Maximum height for preview
 * @return {JSX.Element} Rendered component
 */
export default function ImageField({
    name,
    label,
    value = 0,
    onChange,
    required = false,
    help = '',
    buttonLabel = 'Select Image',
    maxWidth = 300,
    maxHeight = 300,
}) {
    const [mediaId, setMediaId] = useState(value);
    const [mediaUrl, setMediaUrl] = useState(null);

    // Load media URL when component mounts or mediaId changes
    useEffect(() => {
        if (mediaId && !mediaUrl && typeof wp !== 'undefined' && wp.media) {
            wp.media.attachment(mediaId).fetch().then((media) => {
                setMediaUrl(media.url);
            }).catch(() => {
                // Media not found or invalid ID
                setMediaUrl(null);
            });
        }
    }, [mediaId, mediaUrl]);

    const handleSelect = (media) => {
        setMediaId(media.id);
        setMediaUrl(media.url);
        onChange(media.id);
    };

    const handleRemove = () => {
        setMediaId(0);
        setMediaUrl(null);
        onChange(0);
    };

    return (
        <div className="hyperpress-field-image">
            <label className="hyperpress-field-label">
                {label}
                {required && <span className="required"> *</span>}
            </label>

            {typeof wp !== 'undefined' && wp.media ? (
                <MediaUploadCheck>
                    <MediaUpload
                        onSelect={handleSelect}
                        value={mediaId}
                        allowedTypes={['image']}
                        render={({ open }) => (
                            <div className="hyperpress-image-upload">
                                {mediaId && mediaUrl ? (
                                    <div className="hyperpress-image-preview">
                                        <img
                                            src={mediaUrl}
                                            alt={label}
                                            style={{
                                                maxWidth: `${maxWidth}px`,
                                                maxHeight: `${maxHeight}px`,
                                                display: 'block',
                                                marginBottom: '12px',
                                            }}
                                        />
                                        <Flex>
                                            <FlexItem>
                                                <Button onClick={open} variant="secondary">
                                                    Change Image
                                                </Button>
                                            </FlexItem>
                                            <FlexItem>
                                                <Button onClick={handleRemove} variant="tertiary">
                                                    Remove
                                                </Button>
                                            </FlexItem>
                                        </Flex>
                                    </div>
                                ) : (
                                    <Button onClick={open} variant="secondary">
                                        {buttonLabel}
                                    </Button>
                                )}
                            </div>
                        )}
                    />
                </MediaUploadCheck>
            ) : (
                <div className="hyperpress-image-upload">
                    <input
                        type="number"
                        value={mediaId}
                        onChange={(e) => {
                            const id = parseInt(e.target.value) || 0;
                            setMediaId(id);
                            onChange(id);
                        }}
                        placeholder="Enter attachment ID"
                        style={{ width: '200px' }}
                    />
                </div>
            )}

            {help && <p className="description">{help}</p>}
        </div>
    );
}
