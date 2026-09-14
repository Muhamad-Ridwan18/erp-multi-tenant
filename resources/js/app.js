document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-select-module]');
    if (!toggle) {
        return;
    }

    const module = toggle.getAttribute('data-select-module');
    const checked = toggle.getAttribute('data-checked') !== 'false';
    const boxes = document.querySelectorAll(`input[type="checkbox"][data-module="${module}"]`);

    boxes.forEach((box) => {
        box.checked = checked;
    });

    toggle.setAttribute('data-checked', checked ? 'false' : 'true');
    toggle.textContent = checked ? 'Clear' : 'Select all';
});

document.addEventListener('input', (event) => {
    const slugSource = event.target.closest('[data-slug-source]');
    if (!slugSource) {
        return;
    }

    const targetName = slugSource.getAttribute('data-slug-source');
    const target = document.querySelector(`[data-slug-target="${targetName}"]`);
    if (!target || target.dataset.slugTouched === 'true') {
        return;
    }

    target.value = slugSource.value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
});

document.addEventListener('input', (event) => {
    const slugTarget = event.target.closest('[data-slug-target]');
    if (!slugTarget) {
        return;
    }

    slugTarget.dataset.slugTouched = 'true';
});

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-add-line]')) {
        const lines = document.querySelector('[data-lines]');
        const template = document.querySelector('#line-template');
        if (!lines || !template) {
            return;
        }

        const index = lines.querySelectorAll('[data-line]').length;
        const html = template.innerHTML.replaceAll('__INDEX__', String(index));
        lines.insertAdjacentHTML('beforeend', html);
    }

    const remove = event.target.closest('[data-remove-line]');
    if (remove) {
        const line = remove.closest('[data-line]');
        const lines = document.querySelector('[data-lines]');
        if (line && lines && lines.querySelectorAll('[data-line]').length > 1) {
            line.remove();
        }
    }
});

document.addEventListener('change', (event) => {
    const select = event.target.closest('[data-product-select]');
    if (!select) {
        return;
    }

    const line = select.closest('[data-line]');
    const priceInput = line?.querySelector('[data-unit-price]');
    if (!priceInput) {
        return;
    }

    const option = select.selectedOptions[0];
    const price = option?.getAttribute('data-price');
    if (price !== null && price !== undefined && price !== '') {
        priceInput.value = price;
    }
});
