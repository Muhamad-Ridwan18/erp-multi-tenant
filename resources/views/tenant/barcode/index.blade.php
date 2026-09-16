@extends('layouts.app')

@section('title', 'Barcode')
@section('page-title', 'Barcode scan')
@section('page-subtitle', 'Inventory')

@section('content')
    <div class="row g-3 justify-content-center">
        <div class="col-lg-6">
            <x-card>
                <label class="form-label">Scan or type barcode / SKU</label>
                <input type="text" class="form-control form-control-lg" id="barcode-input" autofocus placeholder="Focus here and scan…" autocomplete="off">
                <div class="text-secondary small mt-2">Press Enter to look up. Matches product barcode or SKU.</div>
                <div id="barcode-message" class="mt-3 text-secondary"></div>
            </x-card>

            <x-card class="mt-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <div>
                        <div class="fw-medium">Camera scanner</div>
                        <div class="text-secondary small">Uses device camera via html5-qrcode (HTTPS or localhost).</div>
                    </div>
                    <div class="btn-list">
                        <button type="button" class="btn btn-primary btn-sm" id="camera-start">Start camera</button>
                        <button type="button" class="btn btn-ghost-secondary btn-sm d-none" id="camera-stop">Stop</button>
                    </div>
                </div>
                <div id="qr-reader" class="rounded border bg-dark" style="min-height: 220px; overflow: hidden;"></div>
                <div id="camera-status" class="text-secondary small mt-2"></div>
            </x-card>

            <div id="barcode-result" class="card mt-3 d-none">
                <div class="card-body">
                    <div class="fw-bold fs-3" id="product-name"></div>
                    <div class="text-secondary" id="product-meta"></div>
                    <div class="mt-2">On hand: <strong id="product-stock"></strong></div>

                    <div class="row g-2 mt-3">
                        <div class="col-6">
                            <form method="POST" action="{{ route('tenant.barcode.adjust') }}">
                                @csrf
                                <input type="hidden" name="product_id" id="adjust-product-id">
                                <input type="hidden" name="delta" value="1">
                                <button class="btn btn-success w-100">+1 stock</button>
                            </form>
                        </div>
                        <div class="col-6">
                            <form method="POST" action="{{ route('tenant.barcode.adjust') }}">
                                @csrf
                                <input type="hidden" name="product_id" id="adjust-product-id-minus">
                                <input type="hidden" name="delta" value="-1">
                                <button class="btn btn-outline-warning w-100">−1 stock</button>
                            </form>
                        </div>
                        @can('inventory.scraps.create')
                            <div class="col-12">
                                <form method="POST" action="{{ route('tenant.barcode.scrap') }}">
                                    @csrf
                                    <input type="hidden" name="product_id" id="scrap-product-id">
                                    <input type="hidden" name="quantity" value="1">
                                    <button class="btn btn-outline-danger w-100">Scrap 1</button>
                                </form>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
        <script>
            (() => {
                const input = document.getElementById('barcode-input');
                const message = document.getElementById('barcode-message');
                const result = document.getElementById('barcode-result');
                const lookupUrl = @json(route('tenant.barcode.lookup'));
                const cameraStatus = document.getElementById('camera-status');
                const startBtn = document.getElementById('camera-start');
                const stopBtn = document.getElementById('camera-stop');
                let scanner = null;
                let lastCode = '';
                let lastAt = 0;

                async function lookup(code) {
                    message.textContent = 'Looking up…';
                    result.classList.add('d-none');
                    const res = await fetch(lookupUrl + '?code=' + encodeURIComponent(code), {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (!data.found) {
                        message.textContent = data.message || 'Not found';
                        return;
                    }
                    message.textContent = '';
                    const p = data.product;
                    document.getElementById('product-name').textContent = p.name;
                    document.getElementById('product-meta').textContent = `${p.sku}${p.barcode ? ' · ' + p.barcode : ''}`;
                    document.getElementById('product-stock').textContent = p.stock_qty;
                    document.getElementById('adjust-product-id').value = p.id;
                    document.getElementById('adjust-product-id-minus').value = p.id;
                    const scrap = document.getElementById('scrap-product-id');
                    if (scrap) scrap.value = p.id;
                    result.classList.remove('d-none');
                    input.value = code;
                    input.select();
                }

                input?.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const code = input.value.trim();
                        if (code) lookup(code);
                    }
                });

                function onScanSuccess(decodedText) {
                    const now = Date.now();
                    if (decodedText === lastCode && now - lastAt < 2500) {
                        return;
                    }
                    lastCode = decodedText;
                    lastAt = now;
                    lookup(decodedText.trim());
                }

                startBtn?.addEventListener('click', async () => {
                    if (typeof Html5Qrcode === 'undefined') {
                        cameraStatus.textContent = 'Camera library failed to load.';
                        return;
                    }

                    cameraStatus.textContent = 'Starting camera…';
                    scanner = new Html5Qrcode('qr-reader');

                    try {
                        await scanner.start(
                            { facingMode: 'environment' },
                            { fps: 10, qrbox: { width: 250, height: 250 } },
                            onScanSuccess,
                            () => {}
                        );
                        cameraStatus.textContent = 'Point the camera at a barcode.';
                        startBtn.classList.add('d-none');
                        stopBtn.classList.remove('d-none');
                    } catch (err) {
                        cameraStatus.textContent = 'Camera unavailable: ' + (err?.message || err);
                        scanner = null;
                    }
                });

                stopBtn?.addEventListener('click', async () => {
                    if (!scanner) {
                        return;
                    }
                    try {
                        await scanner.stop();
                        await scanner.clear();
                    } catch (_) {}
                    scanner = null;
                    cameraStatus.textContent = 'Camera stopped.';
                    stopBtn.classList.add('d-none');
                    startBtn.classList.remove('d-none');
                });
            })();
        </script>
    @endpush
@endsection
