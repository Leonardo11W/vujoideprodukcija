@php
$color = $booking_colors->where('sub_type', $data->status)->first()->name ?? '#6c757d';
$isAdmin = auth()->user()->hasRole('admin');
$canEdit = $isAdmin || auth()->user()->can('edit_booking');
$isLocked = ($data->status == 'completed');
$disabled = (!$isAdmin && (!$canEdit || $isLocked)) ? 'disabled' : '';
@endphp

@if($data->status == 'completed')
    @php
    $completedStatusText = $completed_status->value ?? 'Completed';
    $completedColor = $booking_colors->where('sub_type', 'completed')->first()->name ?? '#28a745';
    @endphp
    <span class="badge text-capitalize p-2" style="background-color: {{ $completedColor }}; color: #fff;">{{ $completedStatusText }}</span>
@else
    <select name="branch_for" class="select2 change-select" data-token="{{csrf_token()}}" data-url="{{route('backend.bookings.updateStatus', ['id' => $data->id, 'action_type' => 'update-status'])}}" style="width: 100%;" {{$disabled}}>
      @foreach ($booking_status as $key => $value )
        <option value="{{$value->name}}" {{$data->status == $value->name ? 'selected' : ''}} data-color="{{ $color }}">{{$value->value}}</option>
      @endforeach
    </select>
@endif
