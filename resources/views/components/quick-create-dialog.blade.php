<dialog id="quick-create-dialog" class="w-full max-w-md rounded-xl border border-line bg-panel p-0 shadow-xl backdrop:bg-ink-950/40">
    <form method="dialog" class="border-b border-line px-5 py-3" data-quick-create-dismiss>
        <div class="flex items-center justify-between">
            <h3 class="font-medium text-ink-950" data-quick-create-title>Create</h3>
            <button type="submit" class="text-ink-400 hover:text-ink-700" aria-label="Close">×</button>
        </div>
    </form>
    <form class="space-y-4 px-5 py-4" data-quick-create-form>
        @csrf
        <input type="hidden" data-quick-create-url>
        <input type="hidden" data-quick-create-select>
        <div class="space-y-1.5">
            <label class="block text-sm font-medium text-ink-800">Name</label>
            <input name="name" required class="w-full rounded-lg border border-line px-3 py-2 text-sm" data-quick-name autofocus>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-ink-800">Email</label>
                <input type="email" name="email" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-ink-800">Phone</label>
                <input name="phone" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
        </div>
        <p class="hidden text-xs text-red-600" data-quick-create-error></p>
        <div class="flex justify-end gap-2 pt-1">
            <button type="button" class="rounded-lg px-3 py-2 text-sm text-ink-600 hover:bg-ink-50" data-quick-create-cancel>Cancel</button>
            <button type="submit" class="rounded-lg bg-ink-800 px-4 py-2 text-sm font-medium text-white hover:bg-ink-900">Save</button>
        </div>
    </form>
</dialog>
