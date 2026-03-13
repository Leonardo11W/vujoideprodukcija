@foreach($categories as $key => $category)
    <div class="col">
        <x-category_card :category="$category" />
    </div>
@endforeach 