<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        /**
         * Fórmulas dos KPIs (RF17, RF18, auditoria 18/09/2026):
         *
         * 1. Volume Transacionado (Gross Merchandise Volume - GMV):
         *    Soma de total_amount de pedidos nos estados ['pago', 'em_andamento', 'concluido'].
         *    Pedidos 'pendente' e 'cancelado' são estritamente excluídos para evitar dupla contagem ou faturamento fantasma.
         *    Nota: O volume transacionado reflete o valor bruto negociado entre usuários e não equivale
         *    à receita da plataforma, inexistindo retenção de comissão interna na v1.
         */
        $transactedVolume = (float) Order::whereIn('status', ['pago', 'em_andamento', 'concluido'])->sum('total_amount');

        $metrics = [
            'transacted_volume' => $transactedVolume,
            'total_orders' => Order::count(),
            'paid_orders' => Order::whereIn('status', ['pago', 'em_andamento', 'concluido'])->count(),
            'pending_orders' => Order::where('status', 'pendente')->count(),
            'cancelled_orders' => Order::where('status', 'cancelado')->count(),
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'approved_sellers' => User::whereHas('sellerProfile', fn ($q) => $q->where('status', 'approved'))->count(),
            'total_listings' => Listing::count(),
            'active_listings' => Listing::where('status', 'publicado')->count(),
            'open_reports' => Report::where('status', 'aberta')->count(),
        ];

        $recentReports = Report::with(['reporter', 'listing'])->where('status', 'aberta')->latest()->take(5)->get();
        $recentOrders = Order::with('buyer')->latest()->take(5)->get();

        return view('admin.dashboard', compact('metrics', 'recentReports', 'recentOrders'));
    }
}
