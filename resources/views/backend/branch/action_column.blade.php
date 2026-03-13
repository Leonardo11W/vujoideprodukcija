<div>
    <div class="d-flex gap-2 align-items-center">
        @php
            $user = auth()->user();
            // Check permissions directly - don't rely on can() which might have caching issues
            $hasViewBranch = $user->hasPermissionTo('view_branch');
            $hasBranchGallery = $user->hasPermissionTo('branch_gallery');
            $hasEditBranch = $user->hasPermissionTo('edit_branch');
            
            // Show gallery button ONLY if user has branch_gallery permission
            // view_branch alone is NOT enough - user must have branch_gallery permission
            $showGalleryButton = $hasBranchGallery;
            
            // User can edit ONLY if they have edit_branch permission
            // branch_gallery permission alone is VIEW-ONLY (no edit/add/delete)
            $hasEditPermission = $hasEditBranch;
            $isReadOnly = !$hasEditPermission;
            
            // Determine access mode for logging
            $accessMode = 'No access';
            if (!$hasBranchGallery) {
                $accessMode = 'No branch_gallery permission';
            } elseif ($hasBranchGallery && !$hasEditBranch) {
                $accessMode = 'View-only mode (branch_gallery without edit_branch)';
            } elseif ($hasBranchGallery && $hasEditBranch) {
                $accessMode = 'Full edit mode (branch_gallery + edit_branch)';
            }
            
            \Log::info('🔍 Branch Gallery Action Column Check', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'branch_id' => $data->id ?? 'N/A',
                'has_view_branch' => $hasViewBranch,
                'has_branch_gallery' => $hasBranchGallery,
                'has_edit_branch' => $hasEditBranch,
                'show_gallery_button' => $showGalleryButton,
                'has_edit_permission' => $hasEditPermission,
                'is_read_only' => $isReadOnly,
                'access_mode' => $accessMode,
                'button_will_show' => $showGalleryButton ? 'YES' : 'NO',
                'button_readonly_attr' => $isReadOnly ? 'data-gallery-readonly=true' : 'no readonly attr',
                'user_roles' => $user->roles->pluck('name')->toArray(),
                'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray()
            ]);
        @endphp
        {{-- Gallery Button - Show if user has view_branch OR branch_gallery permission --}}
        @if($showGalleryButton)
            <button type='button' 
                data-gallery-module="{{ $data->id }}" 
                data-gallery-target='#branch-gallery-form'
                data-gallery-event='branch_gallery' 
                @if($isReadOnly) data-gallery-readonly='true' @endif
                class='btn btn-soft-info btn-sm rounded text-nowrap'
                data-bs-toggle="tooltip" 
                title="{{ __('messages.gallery_for_branch') }}">
                <i class="fa-solid fa-images"></i>
            </button>
        @else
            {{-- DEBUG: Button not showing - has_view_branch: {{ $hasViewBranch ? 'true' : 'false' }}, has_branch_gallery: {{ $hasBranchGallery ? 'true' : 'false' }} --}}
        @endif
        @hasPermission('edit_branch')
            <button type="button" class="btn btn-soft-primary btn-sm" data-crud-id="{{ $data->id }}"
                title="{{ __('messages.edit') }} " data-bs-toggle="tooltip"> <i class="fa-solid fa-pen-clip"></i></button>
        @endhasPermission
        @hasPermission('delete_branch')
            <a href="{{ route("backend.$module_name.destroy", $data->id) }}"
                id="delete-{{ $module_name }}-{{ $data->id }}" class="btn btn-soft-danger btn-sm" data-type="ajax"
                data-method="DELETE" data-token="{{ csrf_token() }}" data-bs-toggle="tooltip"
                title="{{ __('messages.delete') }}"
                data-confirm="{{ __('messages.are_you_sure?', ['module' => __('branch.singular_title'), 'name' => $data->name]) }}">
                <i class="fa-solid fa-trash"></i></a>
        @endhasPermission
    </div>
</div>
