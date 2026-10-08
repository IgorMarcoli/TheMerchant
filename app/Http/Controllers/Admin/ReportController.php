<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ModerateReportRequest;
use App\Models\Report;
use App\Services\ReportModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Report::class);

        $status = request()->query('status');
        $reports = Report::with(['reporter', 'listing.seller', 'moderator', 'audits.moderator'])
            ->when(in_array($status, ['aberta', 'em_analise', 'procedente', 'improcedente'], true), fn ($query) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status IN ('aberta', 'em_analise') THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.reports.index', compact('reports', 'status'));
    }

    public function moderate(ModerateReportRequest $request, Report $report, ReportModerationService $service): RedirectResponse
    {
        $this->authorize('moderate', $report);
        $service->moderate($request->user(), $report, $request->validated());

        return back()->with('success', 'Julgamento de moderação registrado.');
    }
}
