@extends('layouts.app')

@section('content')
<!-- Left Navigation Sidebar -->
@include('layouts.sidebar')

<!-- Right Tool Workspace -->
<main class="app-workspace">
    @if(($activeTool ?? '') === 'overview')
        @include('tools.overview')
    @elseif(($activeTool ?? '') === 'image-to-video')
        @include('tools.image-to-video')
    @elseif(($activeTool ?? 'image-generator') === 'video-generator')
        @include('tools.video-generator')
    @else
        @include('tools.image-generator')
    @endif
</main>
@endsection
