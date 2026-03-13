{{-- Expects a $bank object --}}
<div class="bank-list-card">
    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-4">
        <div class="d-flex align-items-center gap-lg-4 gap-2">
            @if($bank->is_default)
            <span class="badge bg-primary rounded-pill">Default</span>
            @endif
            <h5 class="m-0">{{ $bank->bank_name }}</h5>
        </div>
        <div class="d-flex align-items-center gap-lg-4 gap-2 justify-content-end">
            <button class="btn btn-link border-0 text-success fs-5 edit-bank-btn"
                data-bank="{{ htmlspecialchars(json_encode($bank), ENT_QUOTES, 'UTF-8') }}" data-bs-toggle="modal"
                data-bs-target="#bankInfoModal">
                <i class="ph ph-pencil-simple-line align-middle"></i>
            </button>
            <form action="{{ route('bank.destroy', $bank->id) }}" method="POST" class="d-inline delete-bank-form">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-link border-0 text-danger fs-5 delete-bank-btn">
                    <i class="ph ph-trash align-middle"></i>
                </button>
            </form>
            @if(!$bank->is_default)
            <button type="button" class="btn btn-link set-default-btn" data-id="{{ $bank->id }}">
                {{__('frontend.set_as_default')}}
            </button>
            @endif
        </div>
    </div>
    <span>********{{ substr($bank->account_no, -4) }}</span>
</div>