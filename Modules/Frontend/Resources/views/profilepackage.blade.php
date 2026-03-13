@extends('frontend::layouts.master')

@section('content')
<div class="section-spacing">
    <div class="profilepackage-container container">
        <div class="heading-box mb-3">
            <h5 class="font-size-21-3">My Package</h5>
        </div>

        @if($activePackage)
        <div class="profile-package-card p-lg-5 p-3 rounded-3 bg-purple">
            <div class="row gy-3">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-lg-3 gap-2 flex-wrap mb-3">
                        <span class="badge bg-purple text-body border rounded-pill text-uppercase">
                            {{ $activePackage->package->branch->name ?? 'N/A' }}
                        </span>
                        <h5 class="mb-0">{{ $activePackage->package->name }}</h5>
                    </div>
                </div>
                <div class="col-md-4">
                    <div
                        class="d-flex gap-lg-3 gap-2 flex-wrap align-items-center justify-content-start justify-content-md-end mb-lg-4 mb-2">
                        <p class="mb-0 font-size-14">Expiring On: <span class="text-danger">
                                {{ date('d M, Y', strtotime($activePackage->package->end_date)) }}
                            </span></p>
                        <span class="badge bg-success rounded-pill font-size-14">Active</span>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-lg-3 gap-2">
                <div class="package-price d-flex align-items-center gap-3 mb-0">
                    <h4 class="m-0 text-primary">{{ \Currency::format($activePackage->package_price) }}</h4>
                    @if($activePackage->package->discount_price)
                    <del class="fw-semibold">{{ \Currency::format($activePackage->package->discount_price) }}</del>
                    @endif
                </div>
                <span class="font-size-14 fw-semibold">
                    @php
                    $endDate = new DateTime($activePackage->package->end_date);
                    $now = new DateTime();
                    $interval = $now->diff($endDate);
                    $remainingMonths = $interval->format('%m') + $interval->format('%y') * 12;
                    $remainingDays = $interval->format('%d');
                    @endphp
                    @if($remainingMonths > 0)
                    {{ $remainingMonths }} {{ $remainingMonths == 1 ? 'Month' : 'Months' }} Remaining
                    @else
                    {{ $remainingDays }} {{ $remainingDays == 1 ? 'Day' : 'Days' }} Remaining
                    @endif
                </span>
            </div>
            <div class="mt-4">
                <h6 class="mb-3">What's included:</h6>
                <div class="bg-gray-900 p-3 rounded-2">
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-5">
                        @php
                        $services = json_decode($activePackage->package->service ?? '[]', true);
                        @endphp
                        @if(is_array($services))
                        @foreach($services as $service)
                        <li class="d-flex justify-content-between align-items-center gap-2 gap-md-3 flex-wrap">
                            <div class="d-flex gap-lg-4 gap-2">
                                <i class="ph ph-arrow-right font-size-18 icon-color"></i>
                                <span>{{ $service['service_name'] ?? 'N/A' }}</span>
                            </div>
                            <div class="d-flex flex-nowrap align-items-center gap-2 font-size-14">
                                <span>Qty:</span>
                                <span class="heading-color">{{ $service['qty'] ?? 0 }}</span>
                            </div>
                        </li>
                        @endforeach
                        @else
                        @foreach($activePackage->userPackageServices as $service)
                        <li class="d-flex justify-content-between align-items-center gap-2 gap-md-3 flex-wrap">
                            <div class="d-flex gap-lg-4 gap-2">
                                <i class="ph ph-arrow-right font-size-18 icon-color"></i>
                                <span>{{ $service->service_name }}</span>
                            </div>
                            <div class="d-flex flex-nowrap align-items-center gap-2 font-size-14">
                                <span>Qty:</span>
                                <span class="heading-color">{{ $service->qty }}</span>
                            </div>
                        </li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>
        </div>
        @else
        <div class="alert alert-info">
            No active package found.
        </div>
        @endif

        <div class="history-section">
            <div class="heading-box d-flex align-items-center justify-content-between mb-4">
                <h6 class="title-text font-size-21-3 mb-0">History</h6>
            </div>
            <div class="table-responsive">
                <table class="table custom-table" id="packages-table">
                    <thead>
                        <tr>
                            @foreach($columns as $column)
                            <th width="{{ $column['width'] }}">{{ $column['title'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Update the columns definition to use 'name' instead of 'package_name'
        @php
        $columns = array_map(function($col) {
            if ($col['data'] === 'package_name') {
                $col['data'] = 'name';
            }
            return $col;
        }, $columns);
        @endphp
        $('#packages-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('profilepackage.data') }}",
            columns: @json($columns),
            order: [[3, 'desc']],
            pageLength: 10,
            searching: true,
            lengthChange: false,
            pagingType: 'simple_numbers',
            dom: '<"d-flex justify-content-end"f>t<"d-flex justify-content-between align-items-center"ip>',
            language: {
                search: "",
                searchPlaceholder: "Search packages...",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                emptyTable: "<div class='text-center p-4'>No package history found.</div>",
                zeroRecords: "<div class='text-center p-4'>No matching packages found.</div>",
                processing: '<div class="d-flex justify-content-center align-items-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>'
            }
        });
    });
</script>
@endpush