<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JournalItem;
use App\Models\Payment;
use App\Models\ProductServiceCategory;
use App\Models\Revenue;
use App\Models\Vender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Income Summary Report
     */
    public function incomeSummary(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = Revenue::where('revenues.created_by', $creatorId)
            ->leftJoin('product_service_categories', 'revenues.category_id', '=', 'product_service_categories.id')
            ->leftJoin('customers', 'revenues.customer_id', '=', 'customers.id')
            ->select(
                'revenues.*',
                'product_service_categories.name as category_name',
                'customers.name as customer_name'
            );

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('revenues.date', [$request->start_date, $request->end_date]);
        }

        // Filter by customer
        if ($request->has('customer_id') && $request->customer_id != '') {
            $query->where('revenues.customer_id', $request->customer_id);
        }

        // Filter by category
        if ($request->has('category_id') && $request->category_id != '') {
            $query->where('revenues.category_id', $request->category_id);
        }

        $revenues = $query->orderBy('revenues.date', 'desc')->get();
        $totalIncome = $revenues->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'revenues' => $revenues,
                'total_income' => (float) $totalIncome,
                'filters' => [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'customer_id' => $request->customer_id,
                    'category_id' => $request->category_id,
                ]
            ]
        ]);
    }

    /**
     * Expense Summary Report
     */
    public function expenseSummary(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = Payment::where('payments.created_by', $creatorId)
            ->leftJoin('product_service_categories', 'payments.category_id', '=', 'product_service_categories.id')
            ->leftJoin('venders', 'payments.vender_id', '=', 'venders.id')
            ->select(
                'payments.*',
                'product_service_categories.name as category_name',
                'venders.name as vendor_name'
            );

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('payments.date', [$request->start_date, $request->end_date]);
        }

        // Filter by vendor
        if ($request->has('vender_id') && $request->vender_id != '') {
            $query->where('payments.vender_id', $request->vender_id);
        }

        // Filter by category
        if ($request->has('category_id') && $request->category_id != '') {
            $query->where('payments.category_id', $request->category_id);
        }

        $payments = $query->orderBy('payments.date', 'desc')->get();
        $totalExpense = $payments->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'payments' => $payments,
                'total_expense' => (float) $totalExpense,
                'filters' => [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'vender_id' => $request->vender_id,
                    'category_id' => $request->category_id,
                ]
            ]
        ]);
    }

    /**
     * Income vs Expense Summary Report
     */
    public function incomeVsExpense(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $startDate = $request->start_date ?? date('Y-01-01');
        $endDate = $request->end_date ?? date('Y-12-31');

        $totalIncome = Revenue::where('created_by', $creatorId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $totalExpense = Payment::where('created_by', $creatorId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $netProfit = $totalIncome - $totalExpense;

        return response()->json([
            'success' => true,
            'data' => [
                'total_income' => (float) $totalIncome,
                'total_expense' => (float) $totalExpense,
                'net_profit' => (float) $netProfit,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        ]);
    }

    /**
     * Profit & Loss Statement
     */
    public function profitLoss(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $startDate = $request->start_date ?? date('Y-01-01');
        $endDate = $request->end_date ?? date('Y-12-31');

        // Income
        $incomeCategories = ProductServiceCategory::where('created_by', $creatorId)
            ->where('type', 'income')
            ->get();

        $incomeData = [];
        $totalIncome = 0;
        foreach ($incomeCategories as $category) {
            $amount = Revenue::where('created_by', $creatorId)
                ->where('category_id', $category->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');

            $incomeData[] = [
                'category' => $category->name,
                'amount' => (float) $amount,
            ];
            $totalIncome += $amount;
        }

        // Expenses
        $expenseCategories = ProductServiceCategory::where('created_by', $creatorId)
            ->where('type', 'expense')
            ->get();

        $expenseData = [];
        $totalExpense = 0;
        foreach ($expenseCategories as $category) {
            $amount = Payment::where('created_by', $creatorId)
                ->where('category_id', $category->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');

            $expenseData[] = [
                'category' => $category->name,
                'amount' => (float) $amount,
            ];
            $totalExpense += $amount;
        }

        $netProfit = $totalIncome - $totalExpense;

        return response()->json([
            'success' => true,
            'data' => [
                'income' => [
                    'categories' => $incomeData,
                    'total' => (float) $totalIncome,
                ],
                'expenses' => [
                    'categories' => $expenseData,
                    'total' => (float) $totalExpense,
                ],
                'net_profit' => (float) $netProfit,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        ]);
    }

    /**
     * Balance Sheet
     */
    public function balanceSheet(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $asOfDate = $request->as_of_date ?? date('Y-m-d');

        // Get all accounts with their balances
        $accounts = ChartOfAccount::with(['accountType', 'accountSubType'])
            ->where('created_by', $creatorId)
            ->get();

        $assets = [];
        $liabilities = [];
        $equity = [];
        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;

        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($accounts as $account) {
            // Calculate balance from journal items
            $debitSum = JournalItem::join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
                ->where('journal_entries.created_by', $creatorId)
                ->where('journal_items.account', $account->id)
                ->where('journal_entries.date', '<=', $asOfDate)
                ->selectRaw("SUM(CAST(COALESCE(NULLIF(journal_items.debit, ''), '0') AS NUMERIC)) as total")
                ->value('total') ?? 0;

            $creditSum = JournalItem::join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
                ->where('journal_entries.created_by', $creatorId)
                ->where('journal_items.account', $account->id)
                ->where('journal_entries.date', '<=', $asOfDate)
                ->selectRaw("SUM(CAST(COALESCE(NULLIF(journal_items.credit, ''), '0') AS NUMERIC)) as total")
                ->value('total') ?? 0;

            $typeName = $account->accountType?->name ?? '';
            $subTypeName = $account->accountSubType?->name ?? 'Other';
            
            // Determine balance based on account type
            if ($typeName === 'Assets') {
                $balance = $debitSum - $creditSum;
                if ($balance != 0) {
                    if (!isset($assets[$subTypeName])) $assets[$subTypeName] = ['name' => $subTypeName, 'total' => 0, 'items' => []];
                    $assets[$subTypeName]['items'][] = ['id' => $account->id, 'name' => $account->name, 'code' => $account->code, 'balance' => (float)$balance];
                    $assets[$subTypeName]['total'] += $balance;
                    $totalAssets += $balance;
                }
            } elseif ($typeName === 'Liabilities') {
                $balance = $creditSum - $debitSum;
                if ($balance != 0) {
                    if (!isset($liabilities[$subTypeName])) $liabilities[$subTypeName] = ['name' => $subTypeName, 'total' => 0, 'items' => []];
                    $liabilities[$subTypeName]['items'][] = ['id' => $account->id, 'name' => $account->name, 'code' => $account->code, 'balance' => (float)$balance];
                    $liabilities[$subTypeName]['total'] += $balance;
                    $totalLiabilities += $balance;
                }
            } elseif ($typeName === 'Equity') {
                $balance = $creditSum - $debitSum;
                if ($balance != 0) {
                    if (!isset($equity[$subTypeName])) $equity[$subTypeName] = ['name' => $subTypeName, 'total' => 0, 'items' => []];
                    $equity[$subTypeName]['items'][] = ['id' => $account->id, 'name' => $account->name, 'code' => $account->code, 'balance' => (float)$balance];
                    $equity[$subTypeName]['total'] += $balance;
                    $totalEquity += $balance;
                }
            } elseif ($typeName === 'Income') {
                $balance = $creditSum - $debitSum;
                $totalIncome += $balance;
            } elseif ($typeName === 'Expenses' || $typeName === 'Costs of Goods Sold') {
                $balance = $debitSum - $creditSum;
                $totalExpense += $balance;
            }
        }
        
        $netIncome = $totalIncome - $totalExpense;
        if ($netIncome != 0) {
             if (!isset($equity['Current Year Earnings'])) $equity['Current Year Earnings'] = ['name' => 'Current Year Earnings', 'total' => 0, 'items' => []];
             $equity['Current Year Earnings']['items'][] = ['id' => -1, 'name' => 'Net Income', 'code' => '', 'balance' => (float)$netIncome];
             $equity['Current Year Earnings']['total'] += $netIncome;
             $totalEquity += $netIncome;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'assets' => array_values($assets),
                'liabilities' => array_values($liabilities),
                'equity' => array_values($equity),
                'totalAssets' => (float)$totalAssets,
                'totalLiabilities' => (float)$totalLiabilities,
                'totalEquity' => (float)$totalEquity,
                'totalLiabilitiesAndEquity' => (float)($totalLiabilities + $totalEquity),
                'as_of_date' => $asOfDate,
            ]
        ]);
    }

    /**
     * Trial Balance
     */
    public function trialBalance(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $startDate = $request->start_date ?? date('Y-01-01');
        $endDate = $request->end_date ?? date('Y-12-31');

        $accounts = ChartOfAccount::where('created_by', $creatorId)->get();

        $trialBalanceData = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $debitSum = JournalItem::join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
                ->where('journal_entries.created_by', $creatorId)
                ->where('journal_items.account', $account->id)
                ->whereBetween('journal_entries.date', [$startDate, $endDate])
                ->sum('journal_items.debit');

            $creditSum = JournalItem::join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
                ->where('journal_entries.created_by', $creatorId)
                ->where('journal_items.account', $account->id)
                ->whereBetween('journal_entries.date', [$startDate, $endDate])
                ->sum('journal_items.credit');

            if ($debitSum > 0 || $creditSum > 0) {
                $trialBalanceData[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'debit' => (float) $debitSum,
                    'credit' => (float) $creditSum,
                ];

                $totalDebit += $debitSum;
                $totalCredit += $creditSum;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'accounts' => $trialBalanceData,
                'total_debit' => (float) $totalDebit,
                'total_credit' => (float) $totalCredit,
                'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        ]);
    }

    /**
     * Account Statement (Ledger)
     */
    public function accountStatement(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $accountId = $request->account_id;
        $startDate = $request->start_date ?? date('Y-01-01');
        $endDate = $request->end_date ?? date('Y-12-31');

        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'Account ID is required'
            ], 422);
        }

        $account = ChartOfAccount::where('id', $accountId)
            ->where('created_by', $creatorId)
            ->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found'
            ], 404);
        }

        // Get opening balance
        $openingDebit = JournalItem::join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where('journal_entries.created_by', $creatorId)
            ->where('journal_items.account', $accountId)
            ->where('journal_entries.date', '<', $startDate)
            ->sum('journal_items.debit');

        $openingCredit = JournalItem::join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where('journal_entries.created_by', $creatorId)
            ->where('journal_items.account', $accountId)
            ->where('journal_entries.date', '<', $startDate)
            ->sum('journal_items.credit');

        $openingBalance = $openingDebit - $openingCredit;

        // Get transactions
        $transactions = JournalItem::join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where('journal_entries.created_by', $creatorId)
            ->where('journal_items.account', $accountId)
            ->whereBetween('journal_entries.date', [$startDate, $endDate])
            ->select(
                'journal_entries.date',
                'journal_entries.reference',
                'journal_items.description',
                'journal_items.debit',
                'journal_items.credit'
            )
            ->orderBy('journal_entries.date')
            ->get();

        $runningBalance = $openingBalance;
        $transactionData = [];

        foreach ($transactions as $transaction) {
            $runningBalance += ($transaction->debit - $transaction->credit);
            $transactionData[] = [
                'date' => $transaction->date,
                'reference' => $transaction->reference,
                'description' => $transaction->description,
                'debit' => (float) $transaction->debit,
                'credit' => (float) $transaction->credit,
                'balance' => (float) $runningBalance,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'account' => [
                    'code' => $account->code,
                    'name' => $account->name,
                ],
                'opening_balance' => (float) $openingBalance,
                'transactions' => $transactionData,
                'closing_balance' => (float) $runningBalance,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        ]);
    }
}
