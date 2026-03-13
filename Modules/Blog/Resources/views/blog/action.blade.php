<?php $auth_user = auth()->user(); ?>

<div class="d-flex gap-2 align-items-center">
    {{-- 🔧 Edit Button --}}
    @if(!$blog->trashed() && $auth_user->can('edit_blog'))
        <a class="btn btn-soft-primary btn-sm" href="{{ route('backend.blog.create', ['id' => $blog->id]) }}"
           title="{{ __('messages.edit') }} {{ __($module_title) }}" data-bs-toggle="tooltip">
            <i class="fa-solid fa-pen-clip"></i>
        </a>
    @endif

    {{-- 🗑 Delete Button --}}
    @if(!$blog->trashed() && $auth_user->can('delete_blog'))
        <a href="{{ route('backend.blog.destroy', $blog->id) }}"
           id="delete-blog-{{ $blog->id }}"
           class="btn btn-soft-danger btn-sm"
           data-type="ajax"
           data-method="DELETE"
           data-token="{{ csrf_token() }}"
           data-bs-toggle="tooltip"
           title="{{ __('messages.delete') }} {{ __($module_title) }}"
           data-confirm="{{ __('messages.are_you_sure?', ['module' => __('messages.blogs'), 'name' => $blog->title ?? __('Unknown')]) }}">
            <i class="fa-solid fa-trash"></i>
        </a>
    @endif

    {{-- ♻️ Restore & 🔥 Force Delete --}}
    @if($auth_user->hasRole('admin') && $blog->trashed())
        <a class="btn btn-soft-success btn-sm restore-tax"
           href="{{ route('backend.blog.action', ['id' => $blog->id, 'type' => 'restore']) }}"
           data-confirm-message="{{ __('messages.are_you_sure_restore') }}"
           data-success-message="{{ __('messages.restore_form') }}"
           title="{{ __('messages.restore') }}"
           data-bs-toggle="tooltip">
            <i class="ph ph-arrow-clockwise align-middle"></i>
        </a>

        <a class="btn btn-soft-danger btn-sm restore-tax"
           href="{{ route('backend.blog.action', ['id' => $blog->id, 'type' => 'forcedelete']) }}"
           data-confirm-message="{{ __('messages.are_you_sure_restore') }}"
           data-success-message="{{ __('messages.restore_form') }}"
           title="{{ __('messages.permanent_delete') }}"
           data-bs-toggle="tooltip">
            <i class="ph ph-trash align-middle"></i>
        </a>
    @endif
</div>
