<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Queue;
use App\Models\Room;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * Show report filter page
     */
    public function index(Request $request)
    {
        return view('admin.reports.index');
    }

    /**
     * Export Clinic Operational Report (PDF)
     */
    
    public function exportPdf(Request $request)
    {
        $type = $request->get('type', 'month'); // day | week | month
        $now  = Carbon::now();
        $month = (int) $request->get('month', $now->month);
        $year = (int) $request->get('year', $now->year);

        $month = max(1, min(12, $month));
        $year = $year > 0 ? $year : $now->year;

        if ($type === 'day') {
            $start = $now->copy()->startOfDay();
            $end   = $now->copy()->endOfDay();
            $label = 'Daily';
        } elseif ($type === 'week') {
            $start = $now->copy()->startOfWeek();
            $end   = $now->copy()->endOfWeek();
            $label = 'Weekly';
        } else {
            $target = Carbon::createFromDate($year, $month, 1);
            $start = $target->copy()->startOfMonth();
            $end   = $target->copy()->endOfMonth();
            $label = 'Monthly (' . $target->format('M Y') . ')';
        }

        $totalQueues = Queue::whereBetween('created_at', [$start, $end])->count();

        $completedQueues = Queue::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $roomActivities = Room::withCount([
            'queues as completed_count' => function ($q) use ($start, $end) {
                $q->where('status', 'completed')
                  ->whereBetween('created_at', [$start, $end]);
            }
        ])->get();

        $manualCancelled = Queue::where('status', 'cancelled')
            ->whereBetween('cancelled_at', [$start, $end])
            ->count();

        $autoCancelled = Queue::where('status', 'auto_cancelled')
            ->whereBetween('cancelled_at', [$start, $end])
            ->count();

        // ==============================
        // 5. GENERATE PDF
        // ==============================
        $clinicUser = Auth::guard('clinic')->user();

        $pdf = Pdf::loadView('admin.reports.pdf', [
            'clinicUser'       => $clinicUser,
            'label'            => $label,
            'start'            => $start,
            'end'              => $end,
            'totalQueues'      => $totalQueues,
            'completedQueues'  => $completedQueues,
            'roomActivities'   => $roomActivities,
            'manualCancelled'  => $manualCancelled,
            'autoCancelled'    => $autoCancelled,
        ]);

        return $pdf->download('Clinic_Operational_Report.pdf');
    }
}
