@props(['user'])
{{-- Every role of a user as pills (a user may have several roles). --}}
@foreach ($user->roles() as $role)<x-role-badge :role="$role" />@endforeach
