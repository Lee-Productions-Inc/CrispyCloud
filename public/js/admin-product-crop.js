/* CrispyCloud Step 50A — client-side square cropper for Admin Products.
   Produces a 640x640 PNG uploaded through the existing image field.
   No extra JS packages, external services or database changes. */
(() => {
  'use strict';
  const input = document.getElementById('p-image');
  const preview = document.querySelector('.ap-image-preview');
  if (!input || !preview || !input.form || typeof DataTransfer === 'undefined') return;

  const SIZE = 360;
  const OUTPUT = 640;
  const MAX_BYTES = 4 * 1024 * 1024;
  let approvedFile = null;
  const originalPreview = preview.innerHTML;
  let applying = false;
  let img = null;
  let sourceUrl = null;
  let previewUrl = null;
  let offsetX = 0;
  let offsetY = 0;
  let scale = 1;
  let zoom = 1;
  let mode = 'cover';
  let dragging = null;
  let rendering = false;

  const css = document.createElement('style');
  css.textContent = `
    .cc-crop-overlay[hidden] { display: none !important; }
    .cc-crop-overlay { position: fixed; inset: 0; z-index: 20000; padding: 16px; display: grid; place-items: center; background: rgba(18,24,36,.73); }
    .cc-crop-dialog { width: min(100%, 520px); max-height: min(94vh, 840px); overflow: auto; padding: 20px; border-radius: 18px; background: #fff; color: #18212f; box-shadow: 0 20px 65px rgba(0,0,0,.25); font: inherit; }
    .cc-crop-dialog h3 { margin: 0 0 6px; font-size: 1.2rem; }
    .cc-crop-dialog p { margin: 0 0 14px; color: #576173; font-size: .88rem; }
    .cc-crop-canvas { display: block; width: min(100%, 360px); aspect-ratio: 1; margin: 0 auto 14px; border: 2px solid #e4d4b3; border-radius: 12px; touch-action: none; cursor: grab; background: #fff5df; }
    .cc-crop-canvas:active { cursor: grabbing; }
    .cc-crop-control { display: grid; gap: 6px; margin: 12px 0; }
    .cc-crop-control label { color: #253044; font-weight: 700; font-size: .88rem; }
    .cc-crop-control input { width: 100%; accent-color: #c8102e; }
    .cc-crop-modes, .cc-crop-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .cc-crop-modes button, .cc-crop-actions button, .cc-crop-open { border: 1px solid #d5d9df; background: #fff; color: #243041; border-radius: 10px; padding: 9px 12px; font: inherit; font-weight: 700; cursor: pointer; }
    .cc-crop-modes button[aria-pressed="true"] { border-color: #c8102e; background: #fff0f2; color: #ba0828; }
    .cc-crop-actions { justify-content: flex-end; margin-top: 15px; }
    .cc-crop-actions .cc-crop-save { border-color: #c8102e; background: #c8102e; color: #fff; }
    .cc-crop-error { margin: 8px 0 !important; color: #b42318 !important; font-weight: 700; }
    .cc-crop-open { display: inline-block; margin-top: 8px; font-size: .85rem; }
  `;
  document.head.appendChild(css);

  const overlay = document.createElement('div');
  overlay.className = 'cc-crop-overlay';
  overlay.hidden = true;
  overlay.innerHTML = `
    <div class="cc-crop-dialog" role="dialog" aria-modal="true" aria-labelledby="cc-crop-heading">
      <h3 id="cc-crop-heading">Crop product photo</h3>
      <p>Drag the photo to position it. Use Zoom to adjust the framing. All saved product photos will be 640 × 640 pixels.</p>
      <canvas class="cc-crop-canvas" width="${SIZE}" height="${SIZE}" aria-label="Drag to position product image"></canvas>
      <div class="cc-crop-modes">
        <button type="button" data-mode="cover" aria-pressed="true">Fill square</button>
        <button type="button" data-mode="contain" aria-pressed="false">Fit entire photo</button>
      </div>
      <div class="cc-crop-control"><label for="cc-crop-zoom">Zoom <output id="cc-crop-zoom-label">100%</output></label>
      <input id="cc-crop-zoom" type="range" min="100" max="300" step="1" value="100"></div>
      <p class="cc-crop-error" role="alert" hidden></p>
      <div class="cc-crop-actions"><button type="button" class="cc-crop-cancel">Cancel</button><button type="button" class="cc-crop-save">Use cropped photo</button></div>
    </div>`;
  document.body.appendChild(overlay);

  const canvas = overlay.querySelector('canvas');
  const ctx = canvas.getContext('2d');
  const zoomInput = overlay.querySelector('#cc-crop-zoom');
  const zoomLabel = overlay.querySelector('#cc-crop-zoom-label');
  const error = overlay.querySelector('.cc-crop-error');
  const saveButton = overlay.querySelector('.cc-crop-save');
  const cancelButton = overlay.querySelector('.cc-crop-cancel');
  const modeButtons = [...overlay.querySelectorAll('[data-mode]')];
  const currentButton = document.createElement('button');
  currentButton.className = 'cc-crop-open';
  currentButton.type = 'button';
  currentButton.textContent = 'Crop current photo';
  currentButton.hidden = !preview.querySelector('img');
  input.insertAdjacentElement('afterend', currentButton);
  input.accept = 'image/png,image/jpeg';

  function message(text) {
    error.textContent = text;
    error.hidden = !text;
  }

  function clamp(value, minimum, maximum) {
    return Math.min(Math.max(value, minimum), maximum);
  }

  function boundPosition() {
    const width = img.naturalWidth * scale;
    const height = img.naturalHeight * scale;
    offsetX = clamp(offsetX, Math.min(0, SIZE - width), Math.max(0, SIZE - width));
    offsetY = clamp(offsetY, Math.min(0, SIZE - height), Math.max(0, SIZE - height));
  }

  function baseScale() {
    if (!img) return 1;
    const sx = SIZE / img.naturalWidth;
    const sy = SIZE / img.naturalHeight;
    return mode === 'cover' ? Math.max(sx, sy) : Math.min(sx, sy);
  }

  function updateScale(recenter = false) {
    if (!img) return;
    const next = baseScale() * zoom;
    if (recenter || !scale) {
      offsetX = (SIZE - img.naturalWidth * next) / 2;
      offsetY = (SIZE - img.naturalHeight * next) / 2;
    } else {
      const ratio = next / scale;
      offsetX = SIZE / 2 - (SIZE / 2 - offsetX) * ratio;
      offsetY = SIZE / 2 - (SIZE / 2 - offsetY) * ratio;
    }
    scale = next;
    boundPosition();
    drawPreview();
  }

  function drawPreview() {
    if (!img) return;
    ctx.fillStyle = '#fff5df';
    ctx.fillRect(0, 0, SIZE, SIZE);
    ctx.drawImage(img, offsetX, offsetY, img.naturalWidth * scale, img.naturalHeight * scale);
  }

  function dismiss() {
    overlay.hidden = true;
    img = null;
    dragging = null;
    rendering = false;
    saveButton.disabled = false;
    if (sourceUrl) URL.revokeObjectURL(sourceUrl);
    sourceUrl = null;
  }

  function restoreApproved() {
    const transfer = new DataTransfer();
    if (approvedFile) transfer.items.add(approvedFile);
    applying = true;
    input.files = transfer.files;
    input.dispatchEvent(new Event('change', { bubbles: true }));
    applying = false;
    if (!approvedFile) preview.innerHTML = originalPreview;
  }

  function openFrom(src, isObjectUrl = false) {
    message('');
    if (sourceUrl) URL.revokeObjectURL(sourceUrl);
    sourceUrl = isObjectUrl ? src : null;
    overlay.hidden = false;
    saveButton.disabled = true;
    const candidate = new Image();
    candidate.crossOrigin = 'anonymous';
    candidate.onload = () => {
      img = candidate;
      mode = 'cover';
      zoom = 1;
      zoomInput.value = '100';
      zoomLabel.textContent = '100%';
      modeButtons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.mode === mode)));
      updateScale(true);
      saveButton.disabled = false;
      zoomInput.focus();
    };
    candidate.onerror = () => message('Could not open this photo. Choose a local PNG or JPG file instead.');
    candidate.src = src;
  }

  function readSelected(event) {
    if (applying) return;
    const file = input.files?.[0];
    if (!file) return;
    if (!['image/png', 'image/jpeg'].includes(file.type) && !/\.(png|jpe?g)$/i.test(file.name)) {
      message('Select a PNG, JPG, or JPEG photo.');
      restoreApproved();
      return;
    }
    const url = URL.createObjectURL(file);
    openFrom(url, true);
  }
  input.addEventListener('change', readSelected, true);

  currentButton.addEventListener('click', () => {
    // If an approved crop exists, edit that. Otherwise use the current saved photo.
    if (approvedFile) {
      openFrom(URL.createObjectURL(approvedFile), true);
      return;
    }
    const current = preview.querySelector('img');
    if (!current || !current.src) return;
    openFrom(current.src);
  });

  modeButtons.forEach(button => button.addEventListener('click', () => {
    mode = button.dataset.mode;
    modeButtons.forEach(b => b.setAttribute('aria-pressed', String(b === button)));
    updateScale(true);
  }));
  zoomInput.addEventListener('input', () => {
    zoom = Number(zoomInput.value) / 100;
    zoomLabel.textContent = `${zoomInput.value}%`;
    updateScale();
  });

  canvas.addEventListener('pointerdown', event => {
    if (!img || rendering) return;
    dragging = { x: event.clientX, y: event.clientY, px: offsetX, py: offsetY };
    canvas.setPointerCapture(event.pointerId);
  });
  canvas.addEventListener('pointermove', event => {
    if (!dragging || !img) return;
    const rect = canvas.getBoundingClientRect();
    offsetX = dragging.px + (event.clientX - dragging.x) * SIZE / rect.width;
    offsetY = dragging.py + (event.clientY - dragging.y) * SIZE / rect.height;
    boundPosition();
    drawPreview();
  });
  const stopDrag = () => { dragging = null; };
  canvas.addEventListener('pointerup', stopDrag);
  canvas.addEventListener('pointercancel', stopDrag);

  cancelButton.addEventListener('click', () => {
    dismiss();
    restoreApproved();
  });
  overlay.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      event.preventDefault();
      dismiss();
      restoreApproved();
    }
  });

  saveButton.addEventListener('click', () => {
    if (!img || rendering) return;
    rendering = true;
    saveButton.disabled = true;
    const output = document.createElement('canvas');
    output.width = OUTPUT;
    output.height = OUTPUT;
    const outCtx = output.getContext('2d');
    const ratio = OUTPUT / SIZE;
    outCtx.fillStyle = '#fff5df';
    outCtx.fillRect(0, 0, OUTPUT, OUTPUT);
    outCtx.drawImage(img, offsetX * ratio, offsetY * ratio,
      img.naturalWidth * scale * ratio, img.naturalHeight * scale * ratio);
    try { output.toBlob(blob => {
      rendering = false;
      saveButton.disabled = false;
      if (!blob) {
        message('Unable to crop this image. Try another photo.');
        return;
      }
      if (blob.size > MAX_BYTES) {
        message('Cropped image is over 4 MB. Try another photo.');
        return;
      }
      const cropped = new File([blob], 'crispycloud-product-cropped.png', { type: 'image/png' });
      approvedFile = cropped;
      const transfer = new DataTransfer();
      transfer.items.add(cropped);
      applying = true;
      input.files = transfer.files;
      input.dispatchEvent(new Event('change', { bubbles: true }));
      applying = false;
      if (previewUrl) URL.revokeObjectURL(previewUrl);
      previewUrl = URL.createObjectURL(cropped);
      // A current-image preview is provided even if no other preview script exists.
      const thumb = document.createElement('img');
      thumb.alt = 'Cropped product image preview';
      thumb.src = previewUrl;
      preview.replaceChildren(thumb);
      currentButton.hidden = false;
      currentButton.textContent = 'Adjust crop again';
      dismiss();
    }, 'image/png'); } catch (cropError) {
      rendering = false;
      saveButton.disabled = false;
      message('Cannot crop this photo in the browser. Please choose a local photo instead.');
    }
  });

  input.form.addEventListener('submit', event => {
    if (!overlay.hidden) {
      event.preventDefault();
      message('Apply or cancel your crop before saving the product.');
    }
  });
})();
