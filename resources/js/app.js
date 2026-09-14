import TomSelect from 'tom-select';

const formatRp = (value) => {
    const amount = Number.isFinite(value) ? value : 0;

    return `Rp ${Math.round(amount).toLocaleString('id-ID')}`;
};

const updateLine = (line) => {
    const qty = Number(line.querySelector('[data-qty]')?.value || 0);
    const price = Number(line.querySelector('[data-unit-price]')?.value || 0);
    const amountEl = line.querySelector('[data-line-amount]');
    const hint = line.querySelector('[data-stock-hint]');
    const select = line.querySelector('[data-product-select]');

    if (amountEl) {
        amountEl.textContent = formatRp(qty * price);
    }

    if (hint && select) {
        const option = select.options[select.selectedIndex];
        const stock = Number(option?.dataset?.stock ?? NaN);

        if (! Number.isNaN(stock) && qty > stock) {
            hint.textContent = `Stock on hand: ${stock}. Qty exceeds stock.`;
            hint.classList.remove('hidden');
        } else {
            hint.textContent = '';
            hint.classList.add('hidden');
        }
    }
};

const updateDocumentTotals = (root) => {
    const doc = root?.closest?.('[data-document-lines]') || document.querySelector('[data-document-lines]');
    if (! doc) {
        return;
    }

    let subtotal = 0;
    doc.querySelectorAll('[data-line]').forEach((line) => {
        const qty = Number(line.querySelector('[data-qty]')?.value || 0);
        const price = Number(line.querySelector('[data-unit-price]')?.value || 0);
        subtotal += qty * price;
        updateLine(line);
    });

    const subtotalEl = doc.querySelector('[data-doc-subtotal]');
    if (subtotalEl) {
        subtotalEl.textContent = formatRp(subtotal);
    }
};

const applyProductDefaults = (select) => {
    const line = select.closest('[data-line]');
    if (! line) {
        return;
    }

    const option = select.options[select.selectedIndex];
    const priceInput = line.querySelector('[data-unit-price]');
    const price = option?.dataset?.price;

    if (priceInput && price !== undefined && price !== '') {
        priceInput.value = price;
    }

    updateDocumentTotals(line);
};

const initTomSelect = (el) => {
    if (! el || el.tomselect || el.dataset.tomSelect === 'false') {
        return;
    }

    new TomSelect(el, {
        create: false,
        allowEmptyOption: true,
        maxOptions: null,
        placeholder: el.dataset.placeholder || 'Select…',
        plugins: ['dropdown_input'],
        onInitialize() {
            this.control_input.placeholder = el.dataset.placeholder || 'Search…';
        },
        onChange() {
            if (el.hasAttribute('data-product-select')) {
                applyProductDefaults(el);
            }
        },
    });
};

const initAllTomSelects = (root = document) => {
    root.querySelectorAll('[data-tom-select]').forEach((el) => initTomSelect(el));
};

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-select-module]');
    if (toggle) {
        const module = toggle.getAttribute('data-select-module');
        const checked = toggle.getAttribute('data-checked') !== 'false';
        const boxes = document.querySelectorAll(`input[type="checkbox"][data-module="${module}"]`);

        boxes.forEach((box) => {
            box.checked = checked;
        });

        toggle.setAttribute('data-checked', checked ? 'false' : 'true');
        toggle.textContent = checked ? 'Clear' : 'Select all';
    }

    if (event.target.closest('[data-add-line]')) {
        const doc = document.querySelector('[data-document-lines]');
        const lines = doc?.querySelector('[data-lines]');
        const template = document.querySelector('#line-template');
        if (! lines || ! template) {
            return;
        }

        const index = lines.querySelectorAll('[data-line]').length;
        const html = template.innerHTML.replaceAll('__INDEX__', String(index));
        lines.insertAdjacentHTML('beforeend', html);

        const newLine = lines.querySelector('[data-line]:last-child');
        newLine?.querySelectorAll('[data-tom-select]').forEach((el) => initTomSelect(el));
        updateDocumentTotals(doc);
    }

    const remove = event.target.closest('[data-remove-line]');
    if (remove) {
        const line = remove.closest('[data-line]');
        const lines = document.querySelector('[data-lines]');
        if (line && lines && lines.querySelectorAll('[data-line]').length > 1) {
            const select = line.querySelector('[data-tom-select]');
            if (select?.tomselect) {
                select.tomselect.destroy();
            }
            line.remove();
            updateDocumentTotals(lines);
        }
    }
});

document.addEventListener('input', (event) => {
    const slugSource = event.target.closest('[data-slug-source]');
    if (slugSource) {
        const targetName = slugSource.getAttribute('data-slug-source');
        const target = document.querySelector(`[data-slug-target="${targetName}"]`);
        if (target && target.dataset.slugTouched !== 'true') {
            target.value = slugSource.value
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    }

    const slugTarget = event.target.closest('[data-slug-target]');
    if (slugTarget) {
        slugTarget.dataset.slugTouched = 'true';
    }

    if (event.target.closest('[data-qty], [data-unit-price]')) {
        updateDocumentTotals(event.target);
    }
});

document.addEventListener('change', (event) => {
    const select = event.target.closest('[data-product-select]');
    if (select) {
        applyProductDefaults(select);
    }
});

document.addEventListener('DOMContentLoaded', () => {
    initAllTomSelects();
    updateDocumentTotals(document);
});
