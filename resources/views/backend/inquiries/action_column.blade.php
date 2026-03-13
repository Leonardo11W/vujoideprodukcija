<div>
    <div class="d-flex gap-2 align-items-center">
        @hasPermission('view_inquiry')
            <a href="{{ route("backend.$module_name.show", $data->id) }}" class="btn btn-soft-info btn-sm rounded text-nowrap"
                data-bs-toggle="tooltip" title="{{ __('messages.view') }}">
                <i class="fa-solid fa-eye"></i>
            </a>
        @endhasPermission
        @hasPermission('delete_inquiry')
            <a href="{{ route("backend.$module_name.destroy", $data->id) }}"
                id="delete-{{ $module_name }}-{{ $data->id }}" class="btn btn-soft-danger btn-sm" data-type="ajax"
                data-method="DELETE" data-token="{{ csrf_token() }}" data-bs-toggle="tooltip"
                title="{{ __('messages.delete') }}"
                data-confirm="{{ __('messages.are_you_sure?', ['module' => __('inquiry.singular_title'), 'name' => $data->name]) }}">
                <i class="fa-solid fa-trash"></i>
            </a>
        @endhasPermission
    </div>
</div>
