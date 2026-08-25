@php($active = $sortField === $field)

<th scope="col" class="{{ $class ?? '' }}">
    <button
        type="button"
        wire:click="sortBy('{{ $field }}')"
        class="btn btn-link p-0 text-decoration-none fw-semibold text-reset"
    >
        {{ $label }}
        @if($active)
            <span class="ms-1 text-muted fw-normal">{{ $sortDirection === 'asc' ? 'asc' : 'desc' }}</span>
        @endif
    </button>
</th>
