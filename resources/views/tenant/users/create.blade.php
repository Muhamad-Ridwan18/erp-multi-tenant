@extends('layouts.app')

@section('title', 'Create user')
@section('page-title', 'Create user')

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('tenant.users.store') }}" class="vstack gap-3">
                @csrf
                <x-card>
                    <x-input label="Name" name="name" value="{{ old('name') }}" required autofocus />
                    <x-input label="Email" name="email" type="email" value="{{ old('email') }}" required />
                    <x-input label="Password" name="password" type="password" required help="Minimum 8 characters." />
                </x-card>
                <x-card>
                    <h2 class="h3 mb-3">Roles</h2>
                    <div class="row g-2">
                        @foreach ($roles as $role)
                            <div class="col-sm-6">
                                <label class="form-check border rounded p-2 mb-0">
                                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="form-check-input" @checked(in_array($role->id, old('roles', []), false))>
                                    <span class="form-check-label">{{ $role->name }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </x-card>
                <div class="d-flex gap-2">
                    <x-button>Create</x-button>
                    <x-button href="{{ route('tenant.users.index') }}" variant="ghost">Cancel</x-button>
                </div>
            </form>
        </div>
    </div>
@endsection
