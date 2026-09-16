@extends('layouts.app')

@section('title', 'Journal entries')
@section('page-title', 'Journal entries')
@section('page-subtitle', 'Finance')

@section('content')
    <x-table :headers="['Number', 'Date', 'Journal', 'Reference', 'Status']" title="Entries">
        @forelse ($entries as $entry)
            <tr>
                <td class="font-monospace small"><a href="{{ route('tenant.journal-entries.show', $entry) }}">{{ $entry->number }}</a></td>
                <td>{{ $entry->date?->format('d M Y') }}</td>
                <td>{{ $entry->journal?->name ?: '—' }}</td>
                <td class="text-secondary">{{ $entry->reference ?: '—' }}</td>
                <td><x-badge tone="success">{{ $entry->status }}</x-badge></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-secondary py-4">No journal entries yet.</td></tr>
        @endforelse
    </x-table>
@endsection
