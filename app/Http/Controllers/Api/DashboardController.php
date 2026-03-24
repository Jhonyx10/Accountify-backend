<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\ProductServiceCategory;
use App\Models\ProductServiceUnit;
use App\Models\Revenue;
use App\Models\Tax;
use App\Models\User;
use App\Models\Vender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics and data
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->type == 'super admin') {
            return $this->superAdminDashboard();
        } else {
            return $this->companyDashboard();
        }
    }

    /**
     * Super Admin Dashboard
     */
    private function superAdminDashboard()
    {
        $totalUsers = User::where('type', 'company')->count();
        $totalPaidUsers = User::where('type', 'company')
            ->where('plan', '!=', null)
            ->count();

        $totalOrders = Order::count();
        $totalOrdersPrice = Order::sum('price');
        $totalPlans = Plan::count();

        $mostPurchasedPlan = Plan::select('plans.id', 'plans.name', DB::raw('COUNT(users.id) as total'))
            ->leftJoin('users', 'plans.id', '=', 'users.plan')
            ->groupBy('plans.id', 'plans.name')
            ->orderBy('total', 'desc')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'total_paid_users' => $totalPaidUsers,
                'total_orders' => $totalOrders,
                'total_orders_price' => $totalOrdersPrice,
                'total_plans' => $totalPlans,
                'most_purchased_plan' => $mostPurchasedPlan ? [
                    'id' => $mostPurchasedPlan->id,
                    'name' => $mostPurchasedPlan->name,
                    'total' => $mostPurchasedPlan->total,
                ] : null,
            ]
        ]);
    }

    /**
     * Company Dashboard
     */
    private function companyDashboard()
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        // Latest Income (5 recent revenues)
        $latestIncome = Revenue::where('created_by', $creatorId)
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        // Latest Expense (5 recent payments)
        $latestExpense = Payment::where('created_by', $creatorId)
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        // Income Categories with amounts
        $incomeCategories = ProductServiceCategory::where('created_by', $creatorId)
            ->where('type', 'income')
            ->get();

        $incomeCategoryData = [];
        foreach ($incomeCategories as $category) {
            $amount = Revenue::where('created_by', $creatorId)
                ->where('category_id', $category->id)
                ->sum('amount');

            $incomeCategoryData[] = [
                'name' => $category->name,
                'color' => $category->color,
                'amount' => $amount,
            ];
        }

        // Expense Categories with amounts
        $expenseCategories = ProductServiceCategory::where('created_by', $creatorId)
            ->where('type', 'expense')
            ->get();

        $expenseCategoryData = [];
        foreach ($expenseCategories as $category) {
            $amount = Payment::where('created_by', $creatorId)
                ->where('category_id', $category->id)
                ->sum('amount');

            $expenseCategoryData[] = [
                'name' => $category->name,
                'color' => $category->color,
                'amount' => $amount,
            ];
        }

        // Income vs Expense Bar Chart Data (Last 6 months)
        $incExpBarChartData = $this->getIncExpBarChartData($creatorId);

        // Constants (counts)
        $constants = [
            'taxes' => Tax::where('created_by', $creatorId)->count(),
            'categories' => ProductServiceCategory::where('created_by', $creatorId)->count(),
            'units' => ProductServiceUnit::where('created_by', $creatorId)->count(),
            'bank_accounts' => BankAccount::where('created_by', $creatorId)->count(),
            'invoices' => Invoice::where('created_by', $creatorId)->count(),
            'bills' => Bill::where('created_by', $creatorId)->count(),
            'revenue' => Revenue::where('created_by', $creatorId)->sum('amount'),
            'expense' => Payment::where('created_by', $creatorId)->sum('amount'),
            'pending_invoices' => Invoice::where('created_by', $creatorId)->where('status', '!=', 4)->count(),
        ];

        // Bank Account Details
        $bankAccounts = BankAccount::where('created_by', $creatorId)->get();

        // Recent Invoices (5 most recent)
        $recentInvoices = Invoice::where('created_by', $creatorId)
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        // Weekly Invoice Statistics
        $weeklyInvoice = $this->getWeeklyInvoiceStats($creatorId);

        // Monthly Invoice Statistics
        $monthlyInvoice = $this->getMonthlyInvoiceStats($creatorId);

        return response()->json([
            'success' => true,
            'data' => [
                'latest_income' => $latestIncome,
                'latest_expense' => $latestExpense,
                'income_categories' => $incomeCategoryData,
                'expense_categories' => $expenseCategoryData,
                'inc_exp_chart' => $incExpBarChartData,
                'constants' => $constants,
                'bank_accounts' => $bankAccounts,
                'recent_invoices' => $recentInvoices,
                'weekly_invoice' => $weeklyInvoice,
                'monthly_invoice' => $monthlyInvoice,
                'current_year' => date('Y'),
                'current_month' => date('M'),
            ]
        ]);
    }

    /**
     * Get Income vs Expense Bar Chart Data (Last 6 months)
     */
    private function getIncExpBarChartData($creatorId)
    {
        $months = [];
        $income = [];
        $expense = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = date('M Y', strtotime("-$i months"));
            $monthStart = date('Y-m-01', strtotime("-$i months"));
            $monthEnd = date('Y-m-t', strtotime("-$i months"));

            $months[] = $month;

            $monthIncome = Revenue::where('created_by', $creatorId)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->sum('amount');
            $income[] = (float) $monthIncome;

            $monthExpense = Payment::where('created_by', $creatorId)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->sum('amount');
            $expense[] = (float) $monthExpense;
        }

        return [
            'months' => $months,
            'income' => $income,
            'expense' => $expense,
        ];
    }

    /**
     * Get Weekly Invoice Statistics
     */
    private function getWeeklyInvoiceStats($creatorId)
    {
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime('sunday this week'));

        $totalInvoices = Invoice::where('created_by', $creatorId)
            ->whereBetween('issue_date', [$weekStart, $weekEnd])
            ->count();

        $totalAmount = Invoice::where('created_by', $creatorId)
            ->whereBetween('issue_date', [$weekStart, $weekEnd])
            ->sum('total_amount');

        $paidInvoices = Invoice::where('created_by', $creatorId)
            ->whereBetween('issue_date', [$weekStart, $weekEnd])
            ->where('status', 4) // Assuming 4 is paid status
            ->count();

        return [
            'total_invoices' => $totalInvoices,
            'total_amount' => (float) $totalAmount,
            'paid_invoices' => $paidInvoices,
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
        ];
    }

    /**
     * Get Monthly Invoice Statistics
     */
    private function getMonthlyInvoiceStats($creatorId)
    {
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');

        $totalInvoices = Invoice::where('created_by', $creatorId)
            ->whereBetween('issue_date', [$monthStart, $monthEnd])
            ->count();

        $totalAmount = Invoice::where('created_by', $creatorId)
            ->whereBetween('issue_date', [$monthStart, $monthEnd])
            ->sum('total_amount');

        $paidInvoices = Invoice::where('created_by', $creatorId)
            ->whereBetween('issue_date', [$monthStart, $monthEnd])
            ->where('status', 4) // Assuming 4 is paid status
            ->count();

        return [
            'total_invoices' => $totalInvoices,
            'total_amount' => (float) $totalAmount,
            'paid_invoices' => $paidInvoices,
            'month_start' => $monthStart,
            'month_end' => $monthEnd,
        ];
    }
}
