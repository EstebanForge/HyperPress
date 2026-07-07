class HyperFieldsMedia {
  constructor() {
    if (window.HyperFieldsMedia instanceof HyperFieldsMedia) {
      return window.HyperFieldsMedia;
    }

    this.l10n = (window.hyperpressFields && window.hyperpressFields.l10n) || {};
    this.bind();
    window.HyperFieldsMedia = this;
  }

  t(key, fallback) {
    return this.l10n[key] || fallback;
  }

  bind() {
    document.addEventListener('click', (event) => {
      const button = event.target.closest(
        '.hyperpress-upload-button, .hyperpress-remove-button, ' +
        '.hyperpress-gallery-button, .hyperpress-clear-gallery-button, ' +
        '.hyperpress-remove-image',
      );

      if (!button) {
        return;
      }

      event.preventDefault();

      if (button.classList.contains('hyperpress-upload-button')) {
        this.openSingleFrame(button);
      } else if (button.classList.contains('hyperpress-remove-button')) {
        this.clearSingle(button.getAttribute('data-field'));
      } else if (button.classList.contains('hyperpress-gallery-button')) {
        this.openGalleryFrame(button);
      } else if (button.classList.contains('hyperpress-clear-gallery-button')) {
        this.clearGallery(button.getAttribute('data-field'));
      } else if (button.classList.contains('hyperpress-remove-image')) {
        this.handleRemoveGalleryItem(button);
      }
    });
  }

  getInput(fieldId) {
    return document.getElementById(fieldId);
  }

  // ---- Single attachment (image stores ID, file stores URL) ------------

  openSingleFrame(button) {
    if (typeof wp === 'undefined' || !wp.media) {
      return;
    }

    const fieldId = button.getAttribute('data-field');
    const type = button.getAttribute('data-type') || 'image';
    const input = this.getInput(fieldId);

    const frame = wp.media({
      title: this.t('selectImage', 'Select Image'),
      button: { text: this.t('selectImage', 'Select Image') },
      library: { type: type === 'file' ? '' : type },
      multiple: false,
    });

    frame.on('select', () => {
      const attachment = frame.state().get('selection').first().toJSON();
      if (!input) {
        return;
      }
      input.value = type === 'image' ? attachment.id : (attachment.url || '');
      this.updateSinglePreview(fieldId, type, attachment);
      this.toggleRemoveButton(fieldId, true);
      frame.off('select');
    });

    frame.open();
  }

  updateSinglePreview(fieldId, type, attachment) {
    const wrapper = document.getElementById(fieldId);
    if (!wrapper) {
      return;
    }
    const field = wrapper.closest('.hyperpress-image-field, .hyperpress-file-field');
    const preview = field && field.querySelector('.hyperpress-image-preview, .hyperpress-file-preview');
    if (!preview) {
      return;
    }

    const url = type === 'image'
      ? this.imageUrl(attachment)
      : attachment.url;

    preview.classList.remove('is-empty');
    preview.innerHTML = type === 'image'
      ? `<img src="${this.escapeAttr(url)}" alt="" style="max-width: 150px; max-height: 150px;">`
      : `<a href="${this.escapeAttr(url)}" target="_blank" rel="noopener" class="hyperpress-file-url">${this.escapeHtml(attachment.filename || url)}</a>`;
    preview.style.display = '';
  }

  imageUrl(attachment) {
    if (!attachment || !attachment.sizes) {
      return attachment.url || '';
    }
    const sizes = attachment.sizes;
    return (sizes.thumbnail || sizes.full || {}).url || attachment.url || '';
  }

  toggleRemoveButton(fieldId, show) {
    const wrapper = document.getElementById(fieldId);
    const field = wrapper && wrapper.closest('.hyperpress-image-field, .hyperpress-file-field');
    const removeBtn = field && field.querySelector('.hyperpress-remove-button');
    if (removeBtn) {
      removeBtn.style.display = show ? 'inline-block' : 'none';
    }
  }

  clearSingle(fieldId) {
    const input = this.getInput(fieldId);
    if (input) {
      input.value = '';
    }
    const wrapper = document.getElementById(fieldId);
    const field = wrapper && wrapper.closest('.hyperpress-image-field, .hyperpress-file-field');
    const preview = field && field.querySelector('.hyperpress-image-preview, .hyperpress-file-preview');
    if (preview) {
      if (preview.classList.contains('hyperpress-image-preview')) {
        preview.innerHTML = `<span class="hyperpress-image-placeholder">${this.escapeHtml(this.t('noImage', 'No image selected'))}</span>`;
        preview.classList.add('is-empty');
        preview.style.display = '';
      } else {
        preview.innerHTML = '';
        preview.style.display = 'none';
      }
    }
    this.toggleRemoveButton(fieldId, false);
  }

  // ---- Media gallery (multi-select, comma-joined IDs) -----------------

  openGalleryFrame(button) {
    if (typeof wp === 'undefined' || !wp.media) {
      return;
    }

    const fieldId = button.getAttribute('data-field');
    const multiple = button.getAttribute('data-multiple') === 'true';
    const input = this.getInput(fieldId);

    const frame = wp.media({
      title: this.t('addImages', 'Add Images'),
      button: { text: this.t('addImages', 'Add Images') },
      multiple,
    });

    const selection = frame.state().get('selection');
    this.parseIds(input ? input.value : '').forEach((id) => {
      const attachment = wp.media.attachment(id);
      if (attachment) {
        selection.add(attachment);
      }
    });

    frame.on('select', () => {
      const ids = [];
      frame.state().get('selection').map((attachment) => {
        const { id } = attachment.toJSON();
        if (id) {
          ids.push(id);
        }
      });

      if (input) {
        input.value = ids.join(',');
      }
      this.renderGalleryPreview(fieldId, ids);
      this.toggleClearGallery(fieldId, ids.length > 0);
      frame.off('select');
    });

    frame.open();
  }

  renderGalleryPreview(fieldId, ids) {
    const wrapper = document.getElementById(fieldId);
    if (!wrapper) {
      return;
    }
    const field = wrapper.closest('.hyperpress-media-gallery-field');
    const preview = field && field.querySelector('.hyperpress-gallery-preview');
    if (!preview) {
      return;
    }

    preview.innerHTML = '';
    if (!ids.length) {
      return;
    }

    ids.forEach((id) => {
      const item = document.createElement('div');
      item.className = 'hyperpress-gallery-item';
      item.setAttribute('data-id', id);
      item.style.cssText = 'display: inline-block; margin: 0 10px 10px 0;';
      item.innerHTML =
        '<img src="" alt="" style="max-width: 100px; max-height: 100px; display:none;">' +
        `<button type="button" class="hyperpress-remove-image" data-id="${this.escapeAttr(String(id))}" style="display: block; margin-top: 5px;">` +
        this.escapeHtml(this.t('remove', 'Remove')) +
        '</button>';
      preview.appendChild(item);

      this.fetchAttachmentThumb(id, item.querySelector('img'));
    });
  }

  fetchAttachmentThumb(id, imgEl) {
    if (typeof wp === 'undefined' || !wp.media) {
      return;
    }
    const attachment = wp.media.attachment(id);
    attachment.fetch().done(() => {
      const sizes = attachment.get('sizes');
      const url = sizes
        ? ((sizes.thumbnail || sizes.full || {}).url || attachment.get('url'))
        : attachment.get('url');
      if (url && imgEl) {
        imgEl.src = url;
        imgEl.style.display = '';
      }
    });
  }

  toggleClearGallery(fieldId, show) {
    const wrapper = document.getElementById(fieldId);
    const field = wrapper && wrapper.closest('.hyperpress-media-gallery-field');
    const clearBtn = field && field.querySelector('.hyperpress-clear-gallery-button');
    if (clearBtn) {
      clearBtn.style.display = show ? 'inline-block' : 'none';
    }
  }

  clearGallery(fieldId) {
    const input = this.getInput(fieldId);
    if (input) {
      input.value = '';
    }
    this.renderGalleryPreview(fieldId, []);
    this.toggleClearGallery(fieldId, false);
  }

  handleRemoveGalleryItem(button) {
    const item = button.closest('.hyperpress-gallery-item');
    const idAttr = button.getAttribute('data-id') || (item ? item.getAttribute('data-id') : '');
    const field = button.closest('.hyperpress-media-gallery-field');
    const hidden = field && field.querySelector('input[type="hidden"]');
    if (hidden && hidden.id) {
      this.removeGalleryItem(hidden.id, parseInt(idAttr, 10));
    }
  }

  removeGalleryItem(fieldId, idToRemove) {
    const input = this.getInput(fieldId);
    if (!input) {
      return;
    }
    const ids = this.parseIds(input.value).filter((id) => id !== idToRemove);
    input.value = ids.join(',');
    this.renderGalleryPreview(fieldId, ids);
    this.toggleClearGallery(fieldId, ids.length > 0);
  }

  // ---- Helpers ---------------------------------------------------------

  parseIds(raw) {
    return String(raw || '')
      .split(',')
      .map((v) => v.trim())
      .filter((v) => v !== '')
      .map((v) => parseInt(v, 10))
      .filter((v) => !Number.isNaN(v));
  }

  escapeAttr(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }
}

window.HyperFieldsMedia = new HyperFieldsMedia();
