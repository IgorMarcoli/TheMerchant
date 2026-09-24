<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $reports = Report::with(['reporter', 'listing.seller', 'moderator'])
            ->latest()
            ->paginate(15);

        return view('admin.reports.index', compact('reports'));
    }

    public function moderate(Request $request, Report $report): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:procedente,improcedente,em_analise'],
            'resolution_notes' => ['required', 'string', 'max:1000'],
            'block_listing' => ['nullable', 'boolean'],
        ]);

        $report->update([
            'status' => $validated['status'],
            'resolution_notes' => $validated['resolution_notes'],
            'moderator_id' => auth()->id(),
        ]);

        // Se a denúncia foi procedente e foi solicitado o bloqueio
        if ($validated['status'] === 'procedente' && ($validated['block_listing'] ?? false)) {
            $report->listing?->update(['status' => 'bloqueado']);
        }

        return back()->with('success', 'Julgamento de moderação registrado.');
    }
}
