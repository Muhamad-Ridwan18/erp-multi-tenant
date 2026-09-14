@extends('layouts.app')

@section('title', 'Create role')
@section('page-title', 'Create role')
@section('page-subtitle', 'Add a custom role for this tenant')

@section('content')
    <div class="row">
        <div class="col-lg-6">
            <x-card>
                <form method="POST" action="{{ route('tenant.roles.store') }}">
                    @csrf
                    <x-input label="Role name" name="name" value="{{ old('name') }}" required autofocus />
                    <div class="d-flex align-items-center gap-2">
                        <x-button>Create</x-button>
                        <x-button href="{{ route('tenant.roles.index') }}" variant="ghost">Cancel</x-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
@endsection
