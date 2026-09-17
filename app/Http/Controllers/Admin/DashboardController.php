<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Report;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $metrics = [
            'total_users'     => User::count(),
            'total_listings'  => Listing::count(),
            'active_listings' => Listing::where('status', 'publicado')->count(),
            'total_orders'    => Order::count(),
            'total_volume'    => Order::whereIn('status', ['pago', 'concluido'])->sum('total_amount'),
            'open_reports'    => Report::where('status', 'aberta')->count(),
        ];

        $recentReports = Report::with(['reporter', 'listing'])->where('status', 'aberta')->latest()->take(5)->get();

        return view('admin.dashboard', compact('metrics', 'recentReports'));
    }
}
