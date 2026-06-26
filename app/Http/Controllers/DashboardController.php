<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Topic;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // obtiene total, no procesados (pre-ia) y sin clasificar por ia (fallback)
        $total    = Ticket::count();
        $preAi    = Ticket::where('topic_id', config('app.general_topic'))->count();
        $fallback = Ticket::where('topic_id', config('app.fallback_topic'))->count();

        // obtiene urgentes
        $urgency = Topic::where('topic', 'Urgencia')->first();
        $urgent  = $urgency ? Ticket::where('topic_id', $urgency->topic_id)->count() : 0;

        // Nombres de tabla con prefijo para DB::raw (no los pases directamente a where/groupBy)
        $p  = DB::getTablePrefix();
        $fT = $p . (new Ticket)->getTable();   // ej. ost_ticket
        $fP = $p . (new Topic)->getTable();    // ej. ost_help_topic

        // Distribución por categoría (excluye sin clasificar y pre-ia)
        $byCategory = Ticket::query()
            ->join((new Topic)->getTable(), DB::raw("`$fT`.`topic_id`"), '=', DB::raw("`$fP`.`topic_id`"))
            ->whereNotIn(DB::raw("`$fT`.`topic_id`"), [config('app.general_topic'), config('app.fallback_topic')])
            ->groupByRaw("`$fP`.`topic_id`, `$fP`.`topic`")
            ->selectRaw("`$fP`.`topic`, count(*) as total")
            ->orderByRaw('count(*) desc')
            ->get();

        // Tickets por día — últimos 30 días
        $rawDaily = Ticket::query()
            ->selectRaw('DATE(created) as date, count(*) as total')
            ->where('created', '>=', Carbon::now()->subDays(29)->startOfDay())
            ->groupByRaw('DATE(created)')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $dailyLabels = [];
        $dailyData   = [];
        for ($i = 29; $i >= 0; $i--) {
            $date          = Carbon::now()->subDays($i)->format('Y-m-d');
            $dailyLabels[] = Carbon::now()->subDays($i)->format('d/m');
            $dailyData[]   = $rawDaily[$date]->total ?? 0;
        }

        // Últimos 15 tickets
        $recent = Ticket::query()
            ->leftJoin((new Topic)->getTable(), DB::raw("`$fT`.`topic_id`"), '=', DB::raw("`$fP`.`topic_id`"))
            ->selectRaw("`$fT`.`ticket_id`, `$fT`.`number`, `$fT`.`created`, `$fT`.`status_id`, `$fP`.`topic`")
            ->orderByRaw("`$fT`.`created` desc")
            ->limit(15)
            ->get();

        return view('dashboard.index', [
            'stats'       => compact('total', 'preAi', 'fallback', 'urgent'),
            'byCategory'  => $byCategory,
            'dailyLabels' => $dailyLabels,
            'dailyData'   => $dailyData,
            'recent'      => $recent,
        ]);
    }
}

