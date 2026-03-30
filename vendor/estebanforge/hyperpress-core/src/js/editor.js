/**
 * WordPress dependencies
 */
import { registerBlockType } from "@wordpress/blocks";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import {
  PanelBody,
  TextControl,
  TextareaControl,
  ColorPalette,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useEffect, useState } from "@wordpress/element";

function Edit({ attributes, setAttributes, name }) {
  const blockProps = useBlockProps();
  const [fields, setFields] = useState([]);
  const [previewHtml, setPreviewHtml] = useState("");
  const [previewLoading, setPreviewLoading] = useState(false);

  useEffect(() => {
    // Fetch fields from the REST API
    wp.apiFetch({ path: `/hyperblocks/v1/block-fields?name=${name}` })
      .then((data) => {
        setFields(data || []);
      })
      .catch((error) => {
        console.error("Failed to fetch block fields:", error);
        setFields([]);
      });
  }, [name]);

  useEffect(() => {
    // Update preview when attributes change
    updatePreview();
  }, [attributes, name]);

  function onUpdateField(key, value) {
    setAttributes({ [key]: value });
  }

  function updatePreview() {
    if (!name || previewLoading) {
      return;
    }

    setPreviewLoading(true);

    wp.apiFetch({
      path: "/hyperblocks/v1/render-preview",
      method: "POST",
      data: {
        blockName: name,
        attributes: attributes,
      },
    })
      .then((response) => {
        if (response.success) {
          setPreviewHtml(response.html);
        } else {
          setPreviewHtml(
            `<div class="hyperblocks-error">Preview Error: ${response.error}</div>`,
          );
        }
      })
      .catch((error) => {
        console.error("Failed to fetch preview:", error);
        setPreviewHtml(
          `<div class="hyperblocks-error">Preview failed to load</div>`,
        );
      })
      .finally(() => {
        setPreviewLoading(false);
      });
  }

  function renderFieldControl(field) {
    const commonProps = {
      key: field.name,
      label: field.label,
      value: attributes[field.name],
      onChange: (value) => onUpdateField(field.name, value),
    };

    switch (field.type) {
      case "text":
        return (
          <TextControl
            {...commonProps}
            __next40pxDefaultSize={true}
            __nextHasNoMarginBottom={true}
          />
        );
      case "textarea":
        return (
          <TextareaControl
            {...commonProps}
            __next40pxDefaultSize={true}
            __nextHasNoMarginBottom={true}
          />
        );
      case "color":
        return (
          <ColorPalette
            {...commonProps}
            colors={[
              { name: "Red", color: "#f00" },
              { name: "Blue", color: "#00f" },
              { name: "Green", color: "#0f0" },
              { name: "Yellow", color: "#fff000" },
              { name: "Black", color: "#000000" },
              { name: "White", color: "#ffffff" },
            ]}
          />
        );
      case "url":
        return (
          <TextControl
            {...commonProps}
            type="url"
            __next40pxDefaultSize={true}
            __nextHasNoMarginBottom={true}
          />
        );
      // Add more field types here as needed (e.g., image, select, checkbox)
      default:
        return (
          <TextControl
            {...commonProps}
            help={`Unknown field type: ${field.type}`}
            __next40pxDefaultSize={true}
            __nextHasNoMarginBottom={true}
          />
        );
    }
  }

  return (
    <div {...blockProps}>
      <InspectorControls>
        <PanelBody title={__("Block Settings", "hyperblocks")}>
          {fields.map((field) => renderFieldControl(field))}
        </PanelBody>
      </InspectorControls>

      {previewLoading && (
        <div className="hyperblocks-loading">
          <p>{__("Loading preview...", "hyperblocks")}</p>
        </div>
      )}

      {!previewLoading && previewHtml && (
        <div
          className="hyperblocks-preview"
          dangerouslySetInnerHTML={{ __html: previewHtml }}
        />
      )}

      {!previewLoading && !previewHtml && (
        <div className="hyperblocks-placeholder">
          <p>
            {__(
              "Configure your block settings to see a preview.",
              "hyperblocks",
            )}
          </p>
        </div>
      )}
    </div>
  );
}

// Register each fluent block with the Edit component
if (window.hyperBlocksConfig) {
  window.hyperBlocksConfig.forEach(function (blockConfig) {
    registerBlockType(blockConfig.name, {
      title: blockConfig.title,
      icon: blockConfig.icon,
      category: "widgets",
      edit: Edit,
      save: function () {
        return null;
      }, // Server-side rendering
    });
  });
}
