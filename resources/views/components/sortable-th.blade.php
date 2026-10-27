@props(['column', 'label'])
@php
    $active = request('sort') === $column;
    $direction = request('direction', 'asc');
    $nextDirection = $active ? ($direction === 'asc' ? 'desc' : null) : 'asc';
    $query = request()->query();
    unset($query['page']);

    if ($nextDirection) {
        $query['sort'] = $column;
        $query['direction'] = $nextDirection;
    } else {
        unset($query['sort'], $query['direction']);
    }

    $sortUrl = route(request()->route()->getName(), $query);
@endphp
<th aria-sort="{{ $active ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
    <a class="sortable-heading {{ $active ? 'is-sorted' : '' }}" href="{{ $sortUrl }}">
        <span>{{ $label }}</span>
        <span class="sort-indicator" aria-hidden="true">{{ $active ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}</span>
    </a>
</th>
