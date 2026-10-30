@php
    $noticeMode = $mode ?? request('mode', 'SHOP');
    $pendingComplaints = \App\Models\Complaint::where('state', 'PENDIENTE')->count();
    $groups = [
        ['label' => 'Área de trabajo', 'items' => [
            ['label'=>'Inicio', 'icon'=>'home', 'url'=>route('home.index'), 'active'=>request()->routeIs('home.index')],
            ['label'=>'Mapa de casetas', 'icon'=>'map-marked-alt', 'url'=>route('casetas.map'), 'active'=>request()->routeIs('casetas.map')],
        ]],
        ['label' => 'Control municipal', 'items' => [
            ['label'=>'Casetas', 'icon'=>'store', 'url'=>route('casetas.index'), 'active'=>request()->routeIs('casetas.*') && !request()->routeIs('casetas.map', 'casetas.legacy*')],
            ['label'=>'Certificaciones', 'icon'=>'file-alt', 'url'=>route('certificate.index'), 'active'=>request()->routeIs('certificate.*')],
            ['label'=>'Notificaciones', 'icon'=>'clipboard-check', 'url'=>route('notices.index', ['mode'=>'SHOP']), 'active'=>request()->routeIs('notices.*') && $noticeMode !== 'WAYS'],
            ['label'=>'Ocupación de vías', 'icon'=>'road', 'url'=>route('notices.index', ['mode'=>'WAYS']), 'active'=>request()->routeIs('notices.*') && $noticeMode === 'WAYS'],
        ]],
        ['label' => 'Movilidad urbana', 'items' => [
            ['label'=>'Parqueo de vehículos', 'icon'=>'car-side', 'url'=>route('carparking.index'), 'active'=>request()->routeIs('carparking.*')],
            ['label'=>'Parqueo de motos', 'icon'=>'motorcycle', 'url'=>route('motoparking.index'), 'active'=>request()->routeIs('motoparking.*')],
            ['label'=>'Restricción vehicular', 'icon'=>'draw-polygon', 'url'=>route('public.parking', ['vista'=>'perimetro']).'#parking', 'active'=>false, 'external'=>true],
        ]],
        ['label' => 'Gestión y seguimiento', 'items' => [
            ['label'=>'Denuncias', 'icon'=>'exclamation-circle', 'url'=>route('complaints.index'), 'active'=>request()->routeIs('complaints.*'), 'badge'=>$pendingComplaints],
            ['label'=>'Avisos', 'icon'=>'bell', 'url'=>route('notifications.index'), 'active'=>request()->routeIs('notifications.*')],
            ['label'=>'Sindicatos y cooperativas', 'icon'=>'users', 'url'=>route('groups.index'), 'active'=>request()->routeIs('groups.*')],
            ['label'=>'Inspectores', 'icon'=>'user-shield', 'url'=>route('inspectors.index'), 'active'=>request()->routeIs('inspectors.*')],
            ['label'=>'Reporte de casetas', 'icon'=>'chart-bar', 'url'=>route('reports.shops'), 'active'=>request()->routeIs('reports.*')],
        ]],
    ];
    if (auth()->user()->can('manage-system')) {
        $groups[] = ['label'=>'Administración', 'items'=>[
            ['label'=>'Perfiles de acceso', 'icon'=>'user-cog', 'url'=>route('user-access.index'), 'active'=>request()->routeIs('user-access.*')],
        ]];
    }
    $groups[] = ['label'=>'Mi cuenta', 'items'=>[['label'=>'Cambiar mi contraseña', 'icon'=>'key', 'url'=>route('account-password.edit'), 'active'=>request()->routeIs('account-password.*')]]];
    $reserved = [];
    if (config('features.caseta_dossier')) {
        $reserved[] = ['label'=>'Documentos históricos', 'icon'=>'archive', 'url'=>route('casetas.legacy'), 'active'=>request()->routeIs('casetas.legacy*')];
    }
    if (config('features.recaudaciones')) {
        $reserved[] = ['label'=>'Recaudaciones', 'icon'=>'receipt', 'url'=>route('recaudaciones.index'), 'active'=>request()->routeIs('recaudaciones.*')];
    }
    if ($reserved) { $groups[] = ['label'=>'Consulta documental', 'items'=>$reserved]; }
@endphp
<aside class="main-sidebar sidebar-dark-primary institutional-sidebar" id="institutional-sidebar" aria-label="Menú principal">
    <a href="{{ route('home.index') }}" class="brand-link institutional-brand">
        <span><img src="{{ asset('img/potosi.png') }}" alt="Gobierno Autónomo Municipal de Potosí" width="42" height="42"></span>
        <span class="brand-text"><strong>Tráfico y Vialidad</strong><small>G.A.M. de Potosí</small></span>
    </a>
    <div class="sidebar">
        <div class="institutional-user">
            <span class="staff-avatar" aria-hidden="true"><i class="fas fa-user"></i></span>
            <div><strong title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</strong><small>{{ $accessLabel }}</small></div>
        </div>
        <div class="menu-search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <label for="menu-search" class="sr-only">Buscar una opción del menú</label>
            <input id="menu-search" type="search" placeholder="Buscar en el menú" autocomplete="off" data-menu-search>
        </div>
        <nav class="institutional-navigation" aria-label="Módulos del sistema">
            @foreach($groups as $group)
                <div class="menu-section" data-menu-section>
                    <h2>{{ $group['label'] }}</h2>
                    <ul class="nav nav-pills nav-sidebar flex-column">
                        @foreach($group['items'] as $item)
                            <li class="nav-item" data-menu-item>
                                <a href="{{ $item['url'] }}" class="nav-link {{ $item['active'] ? 'active' : '' }}" aria-label="{{ $item['label'] }}{{ ($item['external'] ?? false) ? ', abre una pestaña nueva' : '' }}" title="{{ $item['label'] }}" @if($item['active']) aria-current="page" @endif @if($item['external'] ?? false) target="_blank" rel="noopener" @endif>
                                    <i class="nav-icon fas fa-{{ $item['icon'] }}" aria-hidden="true"></i>
                                    <span>{{ $item['label'] }}</span>
                                    @if(($item['badge'] ?? 0) > 0)<span class="menu-count" aria-label="{{ $item['badge'] }} denuncias pendientes">{{ $item['badge'] }}</span>@endif
                                    @if($item['external'] ?? false)<i class="fas fa-external-link-alt menu-external" aria-hidden="true"></i><span class="sr-only"> (abre una pestaña nueva)</span>@endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
            <p class="menu-empty" data-menu-empty hidden role="status">No hay opciones para esa búsqueda.</p>
        </nav>
        <div class="sidebar-institution">Gobierno Autónomo<br>Municipal de Potosí</div>
    </div>
</aside>
