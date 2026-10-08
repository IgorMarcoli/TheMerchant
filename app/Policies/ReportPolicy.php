<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function moderate(User $user, Report $report): bool
    {
        return $user->isAdmin() && in_array($report->status, ['aberta', 'em_analise'], true);
    }
}
