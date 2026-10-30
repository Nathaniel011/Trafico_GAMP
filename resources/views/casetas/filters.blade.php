<form method="GET" class="field-filters" data-map-filters>
<label>Buscar caseta<input class="form-control" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Número, propietario, CI o dirección"></label>
<label>Licencia<select name="license" class="form-control"><option value="">Todas</option>@foreach(['VIGENTE','SIN LICENCIA','NO VIGENTE','POR VERIFICAR'] as $value)<option value="{{ $value }}" @selected(request('license') === $value)>{{ $value }}</option>@endforeach</select></label>
<label>Notificaciones<select name="stage" class="form-control"><option value="">Todas</option>@foreach(['neutral'=>'Sin avisos activos','yellow'=>'Primera · amarillo','blue'=>'Segunda · azul','green'=>'Tercera · verde','red'=>'Tercera vencida · rojo'] as $key=>$label)<option value="{{ $key }}" @selected(request('stage') === $key)>{{ $label }}</option>@endforeach</select></label>
<button class="btn btn-primary" type="submit">Filtrar</button>
</form>
