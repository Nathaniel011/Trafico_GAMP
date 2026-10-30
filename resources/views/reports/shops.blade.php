@extends('layouts.admin')

@section('header')
    <h1>Reporte casetas</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-tools">
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reports.shops') }}" class="field-filters d-flex flex-wrap align-items-end mb-3">
                <label>Desde <input type="date" class="form-control" name="start" value="{{ old('start', $start) }}"></label>
                <label>Hasta <input type="date" class="form-control" name="end" value="{{ old('end', $end) }}"></label>
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-outline-primary" href="{{ route('reports.shops') }}">Ver todos</a>
            </form>
            @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
            <div class="table-responsive"><table class="table table-hover table-dense" >
                <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha Inspección </th>
                    <th>Propietario</th>
                    <th>Lugar</th>
                    <th>Actividad</th>
                </tr>
                </thead>
                <tbody>
                @forelse($data as $idx => $item)
                    <tr>
                        <td>{{ $data->firstItem() + $idx }}</td>
                        <td>
                            @php
                            $date = $item->inspection_date;
                            if($date!=null){
                                $date = new \Carbon\Carbon($date);
                                $date = $date->format('d/m/Y');
                            }else{
                                $date = '...';
                            }
                            @endphp
                            {{ $date  }}
                        </td>
                        <td>{{$item->solicitante}}</td>
                        <td>{{$item->direccion}}</td>
                        <td>{{$item->rubro}}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No hay registros para el período seleccionado.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer">
            <p class="mb-2">Mostrando {{ $data->firstItem() ?? 0 }} a {{ $data->lastItem() ?? 0 }} de {{ $data->total() }} registros.</p>
            {{ $data->links('pagination::bootstrap-4') }}
        </div>
        <!-- /.card-footer-->
    </div>
    <!-- /.card -->


@endsection

@push('styles')
    <link rel="stylesheet" href="{{asset('plugins/DataTables-1.11.3/css/dataTables.bootstrap4.min.css')}}">
@endpush

@push('scripts')

    <script src="{{asset('plugins/DataTables-1.11.3/js/jquery.dataTables.min.js')}}"></script>
    <script>
        $(function() {

        });
    </script>
@endpush
