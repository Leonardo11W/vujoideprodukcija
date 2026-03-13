@props(['errors'])

@if ($errors->any())
    <div {{ $attributes }}>


        <ul class="mt-3 text-danger">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
