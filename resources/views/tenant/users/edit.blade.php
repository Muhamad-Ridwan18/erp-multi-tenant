@extends('layouts.app')

@section('title', 'Edit user')
@section('page-title', 'Edit user')
@section('page-subtitle', $userModel->email)

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
        @if ($userModel->id !== auth()->id())
            <form method="POST" action="{{ route('tenant.users.destroy', $userModel) }}" onsubmit="return confirm('Delete this user?')" class="ms-auto">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">Delete</x-button>
            </form>
        @endif
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('tenant.users.update', $userModel) }}" class="vstack gap-3">
                @csrf
                @method('PUT')
                <x-card>
                    <x-input label="Name" name="name" value="{{ old('name', $userModel->name) }}" required />
                    <x-input label="Email" name="email" type="email" value="{{ old('email', $userModel->email) }}" required />
                    <x-input label="New password" name="password" type="password" help="Leave blank to keep current password." />
                </x-card>
                <x-card>
                    <h2 class="h3 mb-3">Roles</h2>
                    <div class="row g-2">
                        @foreach ($roles as $role)
                            <div class="col-sm-6">
                                <label class="form-check border rounded p-2 mb-0">
                                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="form-check-input" @checked(in_array($role->id, old('roles', $selectedRoles), false))>
                                    <span class="form-check-label">{{ $role->name }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </x-card>
                <div class="d-flex gap-2">
                    <x-button>Save</x-button>
                    <x-button href="{{ route('tenant.users.index') }}" variant="ghost">Cancel</x-button>
                </div>
            </form>
        </div>
    </div>
@endsection
