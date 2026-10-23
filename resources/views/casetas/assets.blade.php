@once
@push('styles')
<link rel="stylesheet" href="{{ asset('plugins/leaflet/leaflet.css') }}">
@vite('resources/css/casetas.css')
@endpush
@push('scripts')
<script>window.TraficoMaps = @json(config('maps'));</script>
<script src="{{ asset('plugins/leaflet/leaflet.js') }}"></script>
@vite('resources/js/casetas.js')
@endpush
@endonce
