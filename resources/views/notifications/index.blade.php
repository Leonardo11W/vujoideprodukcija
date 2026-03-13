@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2>All Notifications</h2>
    <ul class="list-group">
        @forelse($notifications as $notification)
            <li class="list-group-item {{ $notification->read_at ? '' : 'list-group-item-info' }}">
                <strong>{{ $notification->data['title'] ?? 'Notification' }}</strong><br>
                {!! html_entity_decode($notification->data['message'] ?? '') !!}<br>
                <small>{{ $notification->created_at->format('d/m/Y h:i A') }}</small>
            </li>
        @empty
            <li class="list-group-item">No notifications found.</li>
        @endforelse
    </ul>
    <div class="mt-3">
        {{ $notifications->links() }}
    </div>
</div>
@endsection 
