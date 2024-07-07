@props(['svgWidth' => '20px', 'svgHeight' => '20px', 'id' => '', 'class' => ''])
<div class="{{$class}}" id={{$id}}>
    @component('nodejs.components.svg.pending-icon')
    @endcomponent
</div>