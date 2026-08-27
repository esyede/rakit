{{-- Pagination view, published automatically by System\Paginator::links(). --}}
{{-- Feel free to change it, it will never be overwritten once it is published. --}}
{{-- Available variables: $paginator (the paginator itself) and $elements (see link_elements()). --}}
@if ($paginator->has_pages())
<nav class="pagination-nav">
    <ul class="pagination">
@foreach ($elements as $element)
@if ('separator' === $element['type'])
        <li class="page-item page-dots disabled"><a class="page-link" href="#">{{ $element['label'] }}</a></li>
@elseif ($element['disabled'])
        <li class="{{ $element['type'] }}_page page-item disabled"><a class="page-link" href="#">{{ $element['label'] }}</a></li>
@elseif ($element['active'])
        <li class="page-item active"><a class="page-link" href="#">{{ $element['label'] }}</a></li>
@elseif ('page' === $element['type'])
        <li class="page-item"><a class="page-link" href="{{ $element['url'] }}">{{ $element['label'] }}</a></li>
@else
        <li class="{{ $element['type'] }}_page page-item"><a class="page-link" href="{{ $element['url'] }}">{{ $element['label'] }}</a></li>
@endif
@endforeach
    </ul>
</nav>
@endif
