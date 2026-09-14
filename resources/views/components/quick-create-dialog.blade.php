<dialog id="quick-create-dialog" class="modal-dialog modal-dialog-centered border-0 bg-transparent p-0" style="max-width: 28rem; width: 100%;">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" data-quick-create-title>Create</h5>
            <form method="dialog">
                <button type="submit" class="btn-close" aria-label="Close"></button>
            </form>
        </div>
        <form class="modal-body" data-quick-create-form>
            @csrf
            <input type="hidden" data-quick-create-url>
            <input type="hidden" data-quick-create-select>
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input name="name" required class="form-control" data-quick-name autofocus>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input name="phone" class="form-control">
                </div>
            </div>
            <p class="text-danger small d-none" data-quick-create-error></p>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-ghost-secondary" data-quick-create-cancel>Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</dialog>
