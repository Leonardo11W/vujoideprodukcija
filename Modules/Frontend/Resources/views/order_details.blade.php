@extends('frontend::layouts.master')

@section('title', __('frontend.order_detail'))

@section('content')
<x-breadcrumb :title="__('frontend.order_detail')" />
<div class="order-details-section section-spacing-inner-pages">
    <div class="container">
        <div class="row gy-4">
            <div class="col-md-8">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <i class="ph ph-caret-left align-middle icon-colo font-size-20"></i>
                        <a href="{{ route('myorder') }}" class="btn btn-link text-body font-size-16">{{__("frontend.back")}}</a>
                    </div>

                    @if($order->payment_status == 'paid' && in_array(strtolower($order->delivery_status), ['delivered','completed','complete']))
                    
                    <a href="{{ route('invoice.download', $order->id) }}" class="btn btn-primary">{{__("frontend.download_invoice")}}</a>
                    @endif           

                </div>
                <!-- @if($order->payment_status == 'paid' && in_array(strtolower($order->delivery_status), ['delivered','completed','complete']))
                    <button type="button" class="btn btn-secondary " data-bs-toggle="modal" data-bs-target="#orderReviewModal">{{ __("frontend.rate_now") }}</button>
                    @endif -->
                <div class="mt-5">
                    <div class="order-content d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <h6 class="mb-0">{{__("frontend.order_id")}}</h6>
                        <a href="#" class="btn btn-link font-size-16">
                            @php
                                $isBooking = $order->bookingProduct()->exists();
                                $rawPrefix = $isBooking ? setting('inv_booking_prefix') : setting('inv_prefix');
                                $prefixLower = strtolower(trim((string)$rawPrefix));
                                if (str_contains($prefixLower, 'booking')) {
                                    $finalPrefix = str_ireplace('booking', __('booking.singular_title') ?? 'Booking', $rawPrefix);
                                } elseif (str_contains($prefixLower, 'inv') || str_contains($prefixLower, 'invoice')) {
                                    $finalPrefix = str_ireplace(['inv', 'invoice'], __('booking.download_invoice') ?? 'Invoice', $rawPrefix);
                                } else {
                                    $finalPrefix = $rawPrefix;
                                }
                                $finalPrefix = preg_replace('/(#\s*-\s*)+/', '# - ', trim($finalPrefix));
                            @endphp
                            {{ $finalPrefix . ' ' . $order->id }}
                        </a>
                    </div>
                </div>
                
                <div class="mt-5">
                    <h5>{{__("frontend.order_details")}}</h5>
                    <div class="order-content">
                        <div class="row">
                            <div class="col-lg-4 col-md-6">
                                <h6 class="mb-1">{{__("frontend.date_and_time")}}</h6>
                                <span class="font-size-14">{{ $order->created_at ? $order->created_at->format('d/m/Y \\a\\t H:i') : '-' }}</span>
                            </div>
                            <div class="col-lg-4 col-md-6 mt-3 mt-md-0">
                                <h6 class="mb-1">{{__("frontend.payment")}}</h6>
                                @php
                                
                                    $orderPaymentStatus = $order->payment_status;
                                    if ($orderPaymentStatus) {
                                        $summaryStatus = ucfirst($orderPaymentStatus);
                                        $summaryClass = strtolower($orderPaymentStatus) === 'paid' ? 'text-success' : (strtolower($orderPaymentStatus) === 'pending' ? 'text-warning' : 'text-danger');
                                    } elseif ($statuses->isEmpty()) {
                                        $summaryStatus = 'N/A';
                                        $summaryClass = 'text-danger';
                                    } elseif ($statuses->count() === 1) {
                                        $summaryStatus = ucfirst($statuses->first());
                                        $summaryClass = strtolower($statuses->first()) === 'paid' ? 'text-success' : (strtolower($statuses->first()) === 'pending' ? 'text-warning' : 'text-danger');
                                    } else {
                                        $summaryStatus = 'Partially Paid';
                                        $summaryClass = 'text-warning';
                                    }
                                @endphp
                                <span class="font-size-14 {{ $summaryClass }}">{{ $summaryStatus }}</span>
                            </div>
                            <div class="col-lg-4 col-md-12 mt-3 mt-lg-0">
                                <h6 class="mb-1">{{__('frontend.delivery_status')}}</h6>
                                @php
                                    $status = strtolower($order->delivery_status);
                                    $statusColor = match($status) {
                                        'pending' => 'text-warning',
                                        'confirmed' => 'text-primary',
                                        'cancelled' => 'text-danger',
                                        'complete', 'completed' => 'text-success',
                                        default => 'text-body',
                                    };
                                @endphp
                                <span class="font-size-14 {{ $statusColor }}">{{ __('frontend.' . strtolower($order->delivery_status)) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                 
                    
                    @foreach($order->orderItems as $orderItem)
                   @php
    $product = $orderItem->product_variation->product;
    $image = $product && $product->media && $product->media->count() > 0
        ? $product->media->first()->getFullUrl()
        : asset('img/frontend/product.png');
    $paymentStatus = strtolower($product->payment_status ?? $order->payment_status);
    $paymentStatusClass = $paymentStatus === 'paid' ? 'text-success' : ($paymentStatus === 'pending' ? 'text-warning' : 'text-danger');
@endphp
                    <div class="order-content order-product-info mb-3">
                        <div class="d-flex align-items-center column-gap-4 row-gap-3 flex-sm-nowrap flex-wrap">
                            <div class="order-product-images">
                                <img src="{{ $image }}" class="avatar avatar-70 object-cover" alt="{{ $product->name ?? 'Product Image' }}">
                            </div>
                            <div>
                                <h5>{{ $product->name ?? 'Product Name' }}</h5>
                                <div class="d-flex align-items-center column-gap-5 row-gap-2 flex-wrap">
                                    <div>
                                        <span class="font-size-14">{{__("frontend.price")}}</span>
                                        <span class="text-primary fw-medium">{{ \Currency::format($orderItem->unit_price ?? 0) }}</span>
                                    </div>
                                    <div>
                                        <span class="font-size-14">{{__("frontend.quantity")}}</span>
                                        <span class="heading-color fw-medium">{{ $orderItem->qty ?? 1 }}</span>
                                    </div>
                                   
                                   
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if($order->payment_status == 'paid' && in_array(strtolower($order->delivery_status), ['delivered','completed','complete']))
                <!-- Order Review Modal (rate products) -->
                <div class="modal fade" id="orderReviewModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content bg-gray-900">
                            <div class="modal-header border-0">
                                <h6 class="mb-0">{{ __('frontend.rate_your_products') }}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="order-products-review-form">
                                    @foreach($order->orderItems as $orderItem)
                                        @php $product = $orderItem->product_variation->product; @endphp
                                        <div class="border rounded p-3 mb-3 order-review-item" data-product-id="{{ $product->id ?? '' }}" data-variation-id="{{ $orderItem->product_variation_id }}">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">{{ $product->name ?? '-' }}</h6>
                                                    <div class="row g-2">
                                                        <div class="col-md-3">
                                                            <label class="form-label mb-1">{{ __('frontend.your_rating') }}</label>
                                                            <select class="form-select form-select-sm" name="rating">
                                                                <option value="">{{ __('frontend.select') }}</option>
                                                                @for($i=1;$i<=5;$i++)
                                                                    <option value="{{ $i }}">{{ $i }}</option>
                                                                @endfor
                                                            </select>
                                                        </div>
                                                        <div class="col-md-9">
                                                            <label class="form-label mb-1">{{ __('frontend.your_review') }} <span class="text-muted">({{ __('frontend.optional') }})</span></label>
                                                            <textarea class="form-control form-control-sm" name="review_msg" rows="2" placeholder="{{ __('frontend.write_a_review_optional') }}"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    <div class="d-flex justify-content-end gap-2 mt-2">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('frontend.cancel') }}</button>
                                        <button type="submit" class="btn btn-primary">{{ __('frontend.submit') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="mt-5">
                    <h5>{{__('frontend.shipping_details')}}</h5>
                    <div class="order-content">
                        <h6>{{ $address->first_name ?? '' }} {{ $address->last_name ?? '' }}</h6>
                        <p class="mb-2">
                            {{ $address->address_line_1 ?? '' }}
                            @if(!empty($address->address_line_2)), {{ $address->address_line_2 }}@endif
                            {{ $address->city_data->name ?? '' }}, {{ $address->state_data->name ?? '' }}, {{ $address->country_data->name ?? '' }} - {{ $address->postal_code ?? '' }}
                        </p>
                        <div><span>Contact Number:</span> <a href="#" class="heading-color btn btn-link border-0 ms-lg-3 ms-2 font-size-16">{{ $address->contact_number ?? $order->user->mobile ?? '' }}</a></div>
                        <!-- @if(isset($booking) && $booking->start_date_time)
                        <div><span>Checkout Time:</span> <span class="heading-color fw-medium">{{ \Carbon\Carbon::parse($booking->start_date_time)->format('d/m/Y H:i') }}</span></div>
                        @endif -->
                    </div>
                </div>
                
            </div>
            <div class="col-md-4 payment-section">
                <div class="payment-container">
                    <h6>{{__('frontend.payment_details')}}</h6>
                    <!-- Payment Summary -->
                    @php
                        $bpSubtotal = $order->orderItems->sum(function($bp) { return ($bp->unit_price ?? 0) * ($bp->qty ?? 1); });
                        $bpDiscount = $order->orderItems->sum('discount_value');
                        $tax_data=  json_decode($order->orderGroup->taxes) ?? [];
                      
                    
                    @endphp
                    <div class="payment-summary">
                        <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                            <span class="font-size-14">{{__('frontend.subtotal')}}</span>
                            <span class="heading-color">{{ \Currency::format($bpSubtotal) }}</span>
                        </div>
                        @if($bpDiscount > 0)
                        <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                            <span class="font-size-14">{{__('frontend.discount')}}</span>
                            <span class="text-success">- {{ \Currency::format($bpDiscount) }}</span>
                        </div>
                        @endif

                        @if(isset($order->orderGroup) && $order->orderGroup->total_tax_amount>0)
                        <div class="tax-summary">


                            <div class="d-flex justify-content-between align-items-center gap-3 mb-1 price-item"  type="button" data-bs-toggle="collapse" data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                                <span class="d-flex align-items-center justify-content-between flex-grow-1 gap-3 font-size-14">
                                    <span>{{__("frontend.tax")}}</span>
                                    <i class="ph ph-caret-down ms-2 toggle-icon"></i>
                                </span>
                                <span class="text-danger fw-semibold">{{ \Currency::format($order->orderGroup->total_tax_amount ?? 0) }}</span>
                            </div>

  <div class="collapse" id="collapseExample">
  <div class="card card-body">
            @foreach($tax_data as $tax)
                                <div class="d-flex justify-content-between align-items-center mb-2 px-3">
                                    <span class="font-size-14">
                                        {{ $tax->tax_name }}
                                        @if($tax->tax_type == 'percent')
                                            ({{ $tax->tax_value }}%)
                                        @else
                                            ({{ \Currency::format($tax->tax_value) }})
                                        @endif
                                    </span>
                                    <span class="heading-color">
                                        {{ \Currency::format($tax->tax_amount) }}
                                    </span>
                                </div>
                                @endforeach
                     </div>
               </div>
         
             </div>
               
                        @endif
                        
                        @if($order->shipping_cost>0)
                        <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                            <span class="font-size-14">{{__("frontend.delivery_charges")}}</span>
                            <span class="heading-color">{{ \Currency::format($order->shipping_cost ?? 0) }}</span>
                        </div>
                        @endif
                        <hr class="line-divider">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>{{__("frontend.total")}}</span>
                            <span class="total-value fw-semibold text-primary">{{ \Currency::format($order->total_admin_earnings ?? 0) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('after-scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const form = document.getElementById('order-products-review-form');
  if(form){
    form.addEventListener('submit', function(e){
      e.preventDefault();
      const items = form.querySelectorAll('.order-review-item');
      const requests = [];
      items.forEach(function(row){
        const productId = row.getAttribute('data-product-id');
        const variationId = row.getAttribute('data-variation-id');
        const rating = row.querySelector('select[name="rating"]').value;
        const review = row.querySelector('textarea[name="review_msg"]').value;
        if(productId && rating){
          const fd = new FormData();
          fd.append('product_id', productId);
          if(variationId){ fd.append('product_variation_id', variationId); }
          fd.append('rating', rating);
          if(review){ fd.append('review_msg', review); }
          requests.push(fetch("{{ url('api/add-review') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: fd,
            credentials: 'same-origin'
          }).then(r=>r.json()));
        }
      });
      if(requests.length === 0){ return; }
      Promise.all(requests).then(function(){
        toastr.success("{{ __('frontend.review_submitted_successfully') }}");
        const modalEl = document.getElementById('orderReviewModal');
        if(modalEl){ const m = bootstrap.Modal.getInstance(modalEl); m && m.hide(); }
        setTimeout(()=>window.location.reload(), 600);
      }).catch(function(){
        toastr.error("{{ __('frontend.failed_to_submit_review') }}");
      });
    });
  }
});
</script>
@endpush
@endsection 