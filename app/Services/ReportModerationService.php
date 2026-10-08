<?php

namespace App\Services;

use App\Models\ModerationAudit;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportModerationService
{
    /**
     * @param array{status: string, resolution_notes: string, block_listing?: bool} $data
     */
    public function moderate(User $moderator, Report $report, array $data): Report
    {
        return DB::transaction(function () use ($moderator, $report, $data): Report {
            $lockedReport = Report::query()->lockForUpdate()->findOrFail($report->id);

            if (! in_array($lockedReport->status, ['aberta', 'em_analise'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Esta denúncia já foi encerrada e não pode ser moderada novamente.',
                ]);
            }

            $previousReportStatus = $lockedReport->status;
            $listing = $lockedReport->listing;
            $previousListingStatus = $listing?->status;
            $shouldBlockListing = $data['status'] === 'procedente'
                && (bool) ($data['block_listing'] ?? false)
                && $listing !== null;

            $lockedReport->update([
                'status' => $data['status'],
                'resolution_notes' => $data['resolution_notes'],
                'moderator_id' => $moderator->id,
            ]);

            if ($shouldBlockListing) {
                $listing->update(['status' => 'bloqueado']);
            }

            ModerationAudit::create([
                'report_id' => $lockedReport->id,
                'moderator_id' => $moderator->id,
                'listing_id' => $listing?->id,
                'action' => $shouldBlockListing ? 'denuncia_julgada_anuncio_bloqueado' : 'denuncia_moderada',
                'previous_report_status' => $previousReportStatus,
                'new_report_status' => $lockedReport->status,
                'previous_listing_status' => $previousListingStatus,
                'new_listing_status' => $listing?->fresh()?->status,
                'resolution_notes' => $data['resolution_notes'],
            ]);

            return $lockedReport->fresh(['reporter', 'listing', 'moderator']);
        });
    }
}
