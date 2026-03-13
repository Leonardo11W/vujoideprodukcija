<li class="notification-card bg-gray-800 rounded p-4">
    <div class="">
        <div class="badge d-inline-flex column-gap-5 row-gap-2 flex-wrap rounded-pill bg-primary-subtle mb-3">
            <span>{{ $notification->created_at->diffForHumans() }}</span>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-3">
                @php
                    // Ensure $notification->data is decoded into an array
                    $notificationData = is_array($notification->data) ? $notification->data : json_decode($notification->data, true);
                @endphp
            <div>
               <div class="d-flex gap-2 align-items-center mb-2">
                    <p class="mb-0">{{__('frontend.type')}}:</p>
                    <h6 class="mb-0 text-primary">{{ ucfirst($notificationData['data']['notification_group'] ?? '') }}</h6>
               </div>

                      @php
                         $data = $notificationData['data'] ?? [];
                         $route = '#';
                         
                         if(isset($data['notification_group'])) {
                             if($data['notification_group'] == 'shop' && isset($data['id'])) {
                                 $route = route('order-detail', ['order_id' => $data['id']]);
                             } elseif($data['notification_group'] == 'booking' && isset($data['id'])) {
                                 $route = route('bookings.detail-page', ['id' => $data['id']]);
                             }
                         }
                      @endphp

                 <a href="{{ $route }}" class="text-decoration-none">
                    <h6>{{ $notificationData['data']['type'] ?? '' }}</h6>
                </a>
                 <span>{!! html_entity_decode($notificationData['data']['notification_message'] ?? '') !!}</span>
             </div>               
        </div>
    </div>
</li>