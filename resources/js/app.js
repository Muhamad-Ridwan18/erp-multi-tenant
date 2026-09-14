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
