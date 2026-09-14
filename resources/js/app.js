import TomSelect from 'tom-select';

const formatRp = (value) => {
    const amount = Number.isFinite(value) ? value : 0;

    return `Rp ${Math.round(amount).toLocaleString('id-ID')}`;
};

const lineCalc = (line) => {
    const qty = Number(line.querySelector('[data-qty]')?.value || 0);
    const price = Number(line.querySelector('[data-unit-price]')?.value || 0);
    const discountPercent = Number(line.querySelector('[data-discount]')?.value || 0);
    const taxPercent = Number(line.querySelector('[data-tax]')?.value || 0);
    const base = qty * price;
    const discount = Math.round(base * Math.min(100, Math.max(0, discountPercent)) / 100);
    const afterDiscount = base - discount;
    const tax = Math.round(afterDiscount * Math.min(100, Math.max(0, taxPercent)) / 100);

    return { base, discount, afterDiscount, tax, total: afterDiscount + tax, qty };
};

const updateLine = (line) => {
    const calc = lineCalc(line);
    const amountEl = line.querySelector('[data-line-amount]');
    const hint = line.querySelector('[data-stock-hint]');
    const select = line.querySelector('[data-product-select]');

    if (amountEl) {
        amountEl.textContent = formatRp(calc.total);
    }

    if (hint && select) {
        const option = select.options[select.selectedIndex];
        const stock = Number(option?.dataset?.stock ?? NaN);

        if (! Number.isNaN(stock) && calc.qty > stock) {
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

    let untaxed = 0;
    let discountTotal = 0;
    let taxTotal = 0;
    let grandTotal = 0;

    doc.querySelectorAll('[data-line]').forEach((line) => {
        const calc = lineCalc(line);
        untaxed += calc.afterDiscount;
        discountTotal += calc.discount;
        taxTotal += calc.tax;
        grandTotal += calc.total;
        updateLine(line);
    });

    const map = {
        '[data-doc-subtotal]': untaxed,
        '[data-doc-discount]': discountTotal,
        '[data-doc-tax]': taxTotal,
        '[data-doc-total]': grandTotal,
    };

    Object.entries(map).forEach(([selector, value]) => {
        const el = doc.querySelector(selector);
        if (el) {
            el.textContent = formatRp(value);
        }
    });
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

const activateTab = (root, key) => {
    root.querySelectorAll('[data-tab-trigger]').forEach((btn) => {
        const active = btn.getAttribute('data-tab-trigger') === key;
        btn.setAttribute('aria-selected', active ? 'true' : 'false');
        btn.classList.toggle('border-ink-800', active);
        btn.classList.toggle('text-ink-950', active);
        btn.classList.toggle('border-transparent', ! active);
        btn.classList.toggle('text-ink-500', ! active);
    });

    root.querySelectorAll('[data-tab-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== key);
    });
};

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content
    || document.querySelector('[data-quick-create-form] input[name="_token"]')?.value;

const openQuickCreate = (button) => {
    const dialog = document.getElementById('quick-create-dialog');
    if (! dialog) {
        return;
    }

    const type = button.dataset.quickType || 'customer';
    dialog.querySelector('[data-quick-create-title]').textContent = `Create ${type}`;
    dialog.querySelector('[data-quick-create-url]').value = button.dataset.quickUrl || '';
    dialog.querySelector('[data-quick-create-select]').value = button.dataset.quickSelect || '';
    dialog.querySelector('[data-quick-create-error]')?.classList.add('hidden');
    dialog.querySelector('[data-quick-create-form]')?.reset();
    dialog.showModal();
    dialog.querySelector('[data-quick-name]')?.focus();
};

const submitQuickCreate = async (form) => {
    const url = form.querySelector('[data-quick-create-url]')?.value;
    const selectId = form.querySelector('[data-quick-create-select]')?.value;
    const errorEl = form.querySelector('[data-quick-create-error]');
    const dialog = document.getElementById('quick-create-dialog');

    if (! url) {
        return;
    }

    errorEl?.classList.add('hidden');

    const body = new FormData(form);
    body.delete('_token');

    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body,
    });

    if (! response.ok) {
        const payload = await response.json().catch(() => ({}));
        const message = payload.message
            || Object.values(payload.errors || {}).flat()[0]
            || 'Could not create record.';
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }
        return;
    }

    const record = await response.json();
    const select = document.getElementById(selectId);
    if (select) {
        const label = record.email ? `${record.name} — ${record.email}` : record.name;
        if (select.tomselect) {
            select.tomselect.addOption({ value: String(record.id), text: label });
            select.tomselect.addItem(String(record.id));
        } else {
            const option = new Option(label, record.id, true, true);
            select.add(option);
        }
    }

    dialog?.close();
};

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-select-module]');
    if (toggle) {
        const module = toggle.getAttribute('data-select-module');
        const checked = toggle.getAttribute('data-checked') !== 'false';
        document.querySelectorAll(`input[type="checkbox"][data-module="${module}"]`).forEach((box) => {
            box.checked = checked;
        });
        toggle.setAttribute('data-checked', checked ? 'false' : 'true');
        toggle.textContent = checked ? 'Clear' : 'Select all';
    }

    const tabTrigger = event.target.closest('[data-tab-trigger]');
    if (tabTrigger) {
        const root = tabTrigger.closest('[data-tabs]');
        if (root) {
            activateTab(root, tabTrigger.getAttribute('data-tab-trigger'));
        }
    }

    const quickOpen = event.target.closest('[data-open-quick-create]');
    if (quickOpen) {
        event.preventDefault();
        openQuickCreate(quickOpen);
    }

    if (event.target.closest('[data-quick-create-cancel]')) {
        document.getElementById('quick-create-dialog')?.close();
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
            select?.tomselect?.destroy();
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

    if (event.target.closest('[data-qty], [data-unit-price], [data-discount], [data-tax]')) {
        updateDocumentTotals(event.target);
    }
});

document.addEventListener('change', (event) => {
    const select = event.target.closest('[data-product-select]');
    if (select) {
        applyProductDefaults(select);
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-quick-create-form]');
    if (! form) {
        return;
    }

    event.preventDefault();
    submitQuickCreate(form);
});

document.addEventListener('DOMContentLoaded', () => {
    initAllTomSelects();
    updateDocumentTotals(document);

    document.querySelectorAll('[data-tabs]').forEach((root) => {
        const first = root.querySelector('[data-tab-trigger]')?.getAttribute('data-tab-trigger');
        if (first) {
            activateTab(root, first);
        }
    });
});
