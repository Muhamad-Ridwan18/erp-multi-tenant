@extends('layouts.app')

@section('title', 'Product categories')
@section('page-title', 'Product categories')
@section('page-subtitle', 'Settings')

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <x-table :headers="['Name', 'Parent', 'Products']" title="Categories">
                @forelse ($categories as $category)
                    <tr>
                        <td class="fw-medium">{{ $category->name }}</td>
                        <td class="text-secondary">{{ $category->parent?->name ?? '—' }}</td>
                        <td class="text-secondary">{{ $category->products_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-secondary py-4">No categories yet.</td></tr>
                @endforelse
            </x-table>
        </div>
        <div class="col-lg-4">
            <form method="POST" action="{{ route('tenant.categories.store') }}">
                @csrf
                <x-card>
                    <h2 class="h3 mb-3">New category</h2>
                    <x-input label="Name" name="name" value="{{ old('name') }}" required />
                    <x-select label="Parent" name="parent_id" placeholder="Search category…">
                        <option value="">No parent</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('parent_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </x-select>
                    <x-button>Create category</x-button>
                </x-card>
            </form>
        </div>
    </div>
@endsection
