@extends('backend.layouts.app')

@section('title')
    {{ __($module_action) }} {{ __($module_title) }}
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">{{ __('inquiry.lbl_inquiry_details') }}</h4>
                <div>
                    <a href="{{ route('backend.inquiries.index') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> {{ __('messages.back') }}
                    </a>
                   
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('inquiry.lbl_name') }}</label>
                        <p class="form-control-plaintext">{{ $inquiry->name ?? 'N/A' }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('inquiry.lbl_email') }}</label>
                        <p class="form-control-plaintext">{{ $inquiry->email ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('inquiry.lbl_subject') }}</label>
                <p class="form-control-plaintext">{{ $inquiry->subject ?? 'N/A' }}</p>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('inquiry.lbl_message') }}</label>
                <div class="form-control-plaintext"
                    style="min-height: 150px; background-color: #f8f9fa; padding: 15px; border-radius: 5px;">
                    {{ $inquiry->message ?? 'N/A' }}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('inquiry.lbl_created_at') }}</label>
                        <p class="form-control-plaintext">
                            {{ $inquiry->created_at ? $inquiry->created_at->format('F j, Y H:i') : 'N/A' }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('inquiry.lbl_updated_at') }}</label>
                        <p class="form-control-plaintext">
                            {{ $inquiry->updated_at ? $inquiry->updated_at->format('F j, Y H:i') : 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
