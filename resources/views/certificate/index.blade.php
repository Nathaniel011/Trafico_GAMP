@extends('layouts.admin')

@section('header')
    <h1>Certificaciones</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-tools">
                <ul class="nav nav-pills ml-auto">
                    @if(auth()->user()->can('edit-certificates'))
                    <a class="btn btn-primary" href="{{route('certificate.create')}}">
                        <i class="fa fa-plus"></i>
                        Nueva certificacion
                    </a>
                    @endif
                </ul>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive"><table class="table" id="main-table">
                <thead>
                <tr>
                    <th>Fecha</th>
                    <th>C.I.</th>
                    <th>Solicitante</th>
                    <th>Rubro</th>
                    <th>Ubicación</th>
                    <th>Estado</th>
		    <th>Modalidad</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>

                </tbody>
            </table></div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer">
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
            $('#main-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{route('certificate.data')}}",
                columns: [
                    {data: 'created_at', name: 'created_at',
                        render: function(data, type, row) {
                            if (type === 'display' || type === 'filter') {
                                let date = new Date(data);
                                return date.toLocaleDateString('es-ES');
                            }
                            return data;
                        }
                    },
                    {data: 'ci', name: 'ci'},
                    {data: 'solicitante', name: 'solicitante'},
                    {data: 'rubro', name: 'rubro'},
                    {data: 'direccion', name: 'direccion'},
                    {data: 'estado', name: 'estado'},
		    {data: 'modalidad', name: 'modalidad'},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false}
                ],
                language: {
                    url: "{{asset('plugins/DataTables-1.11.3/es.json')}}"
                }
            });
        });
    </script>
@endpush
