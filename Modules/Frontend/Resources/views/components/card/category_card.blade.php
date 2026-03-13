@props(['category'])

<div class="category-card">
    <div class="category-image">
        <img src="{{ $category->feature_image }}" alt="{{ $category->name }}">
    </div>
    <div class="category-info">
        <h5 class="mb-0 text-truncate">
            <a href="{{ route('service', ['category' => $category->slug]) }}"
                class="text-truncate text-wrap d-inline-block">
                {{ $category->name }}
            </a>
        </h5>
    </div>
    <a href="{{ route('service', ['category' => $category->slug]) }}" class="category-overlay"></a>
</div>
