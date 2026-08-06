@php
    $roleName = strtolower(str_replace(' ', '-', $node->role->name));
    $roleClass = match ($roleName) {
        'sales-manager'    => 'role-sales-manager',
        'area-manager'     => 'role-area-manager',
        'sales-supervisor' => 'role-sales-supervisor',
        'sales-rep'        => 'role-sales-rep',
        default            => 'badge-secondary',
    };

    // Layout rule: reps stack vertically; others side-by-side
    $childrenLayout = $roleName === 'sales-rep' ? 'col' : 'row';

    // Count direct children; add "children-dense" when > 5
    $childCount = $node->children?->count() ?? 0;
    $isDense = $childCount > 5;
@endphp

<li class="org-node {{ $roleName === 'sales-rep' ? 'is-rep' : '' }}">
    <div class="org-box">
        <div class="org-role {{ $roleClass }}">{{ $node->role->name }}</div>
        <div class="org-name">{{ $node->name }}</div>
        @php $target = $node->userTarget();  @endphp
        @if ($target)
            <div class="org-name" style="color: red;">
                <b>{{$target}}</b>
            </div>
        @endif
    </div>

    @if ($childCount)
        <ul class="org-level children-{{ $childrenLayout }} list-unstyled {{ $isDense ? 'children-dense' : '' }}">
            @foreach ($node->children as $child)
                @include('users.org-node', ['node' => $child])
            @endforeach
        </ul>
    @endif
</li>
