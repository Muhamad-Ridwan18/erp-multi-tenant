@extends('layouts.app')

@section('title', 'Create role')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">Create role</h1>

    <form method="POST" action="{{ route('tenant.roles.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-lg space-y-4">
        @csrf
        <div>
            <label class="block text-sm mb-1">Role name</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <button class="rounded-lg bg-slate-900 text-white text-sm px-4 py-2">Create</button>
    </form>
@endsection
