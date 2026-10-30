<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function shops(Request $request){
        $filters = $request->validate([
            'start' => 'nullable|date_format:Y-m-d',
            'end' => ['nullable', 'date_format:Y-m-d', ...($request->filled('start') ? ['after_or_equal:start'] : [])],
        ]);
        $start = $filters['start'] ?? '';
        $end = $filters['end'] ?? '';
        $data = Certificate::query()
            ->when($start !== '', fn ($q) => $q->whereDate('inspection_date', '>=', $start))
            ->when($end !== '', fn ($q) => $q->whereDate('inspection_date', '<=', $end))
            ->orderBy('inspection_date')->orderBy('id')->get();
        return view('reports.shops', [ 'data' => $data, 'start' => $start, 'end' => $end ]);
    }

}
