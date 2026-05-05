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
use Barryvdh\DomPDF\Facade\Pdf;

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

        // Maps DB subtype names → human-readable L1 category labels shown in the report
        $subtypeCategoryMap = [
            // Assets
            'Bank & Cash'       => 'Current Assets',
            'Current Asset'     => 'Current Assets',
            'Inventory'         => 'Current Assets',
            'Prepayment'        => 'Current Assets',
            'Fixed Asset'       => 'Long-term Assets',
            'Non-current Asset' => 'Long-term Assets',
            'Depreciation'      => 'Long-term Assets',
            // Liabilities
            'Current Liability'     => 'Current Liabilities',
            'Liability'             => 'Other Liabilities',
            'Non-current Liability' => 'Long-term Liabilities',
            // Equity
            'Equity' => 'Equity',
        ];

        // Get all accounts with their balances, including parent account info
        $accounts = ChartOfAccount::with(['accountType', 'accountSubType', 'parentAccount'])
            ->where('created_by', $creatorId)
            ->get();

        $sections = [
            'Assets' => ['total' => 0, 'subtypes' => []],
            'Liabilities' => ['total' => 0, 'subtypes' => []],
            'Equity' => ['total' => 0, 'subtypes' => []],
        ];

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
            // L1 label: map subtype to a readable category, fallback to subtype name itself
            $categoryLabel = $subtypeCategoryMap[$subTypeName] ?? $subTypeName;
            // L2 label: the subtype name (e.g. "Bank & Cash")
            $groupLabel = $subTypeName;
            // L3 label source: account's parent account name, fallback to account name
            $parentName = $account->parentAccount?->name ?? $account->name;
            
            $balance = 0;
            if ($typeName === 'Assets') {
                $balance = $debitSum - $creditSum;
            } elseif ($typeName === 'Liabilities' || $typeName === 'Equity') {
                $balance = $creditSum - $debitSum;
            } elseif ($typeName === 'Income') {
                $totalIncome += ($creditSum - $debitSum);
                continue;
            } elseif ($typeName === 'Expenses' || $typeName === 'Costs of Goods Sold') {
                $totalExpense += ($debitSum - $creditSum);
                continue;
            }

            if ($balance != 0 && isset($sections[$typeName])) {
                $sections[$typeName]['total'] += $balance;
                
                // L1: category (e.g. "Current Assets")
                if (!isset($sections[$typeName]['subtypes'][$categoryLabel])) {
                    $sections[$typeName]['subtypes'][$categoryLabel] = [
                        'name' => $categoryLabel,
                        'total' => 0,
                        'groups' => []
                    ];
                }
                $sections[$typeName]['subtypes'][$categoryLabel]['total'] += $balance;
                
                // L2: subtype (e.g. "Bank & Cash")
                if (!isset($sections[$typeName]['subtypes'][$categoryLabel]['groups'][$groupLabel])) {
                    $sections[$typeName]['subtypes'][$categoryLabel]['groups'][$groupLabel] = [
                        'name' => $groupLabel,
                        'total' => 0,
                        'items' => []
                    ];
                }
                $sections[$typeName]['subtypes'][$categoryLabel]['groups'][$groupLabel]['total'] += $balance;
                $sections[$typeName]['subtypes'][$categoryLabel]['groups'][$groupLabel]['items'][] = [
                    'id' => $account->id,
                    'name' => $account->name,
                    'code' => $account->code,
                    'balance' => (float)$balance
                ];
            }
        }
        
        $netIncome = $totalIncome - $totalExpense;
        if ($netIncome != 0) {
            $typeName = 'Equity';
            $subTypeName = 'Current Year Earnings';
            $parentName = 'Net Income';
            
            if (!isset($sections[$typeName]['subtypes'][$subTypeName])) {
                $sections[$typeName]['subtypes'][$subTypeName] = ['name' => $subTypeName, 'total' => 0, 'groups' => []];
            }
            $sections[$typeName]['subtypes'][$subTypeName]['total'] += $netIncome;
            $sections[$typeName]['total'] += $netIncome;
            
            if (!isset($sections[$typeName]['subtypes'][$subTypeName]['groups'][$parentName])) {
                $sections[$typeName]['subtypes'][$subTypeName]['groups'][$parentName] = ['name' => $parentName, 'total' => 0, 'items' => []];
            }
            $sections[$typeName]['subtypes'][$subTypeName]['groups'][$parentName]['total'] += $netIncome;
            $sections[$typeName]['subtypes'][$subTypeName]['groups'][$parentName]['items'][] = [
                'id' => -1,
                'name' => 'Net Income',
                'code' => '',
                'balance' => (float)$netIncome
            ];
        }

        // Convert associative arrays to indexed for JSON
        foreach ($sections as $type => &$section) {
            foreach ($section['subtypes'] as &$subtype) {
                $subtype['groups'] = array_values($subtype['groups']);
            }
            $section['subtypes'] = array_values($section['subtypes']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'assets' => $sections['Assets']['subtypes'],
                'liabilities' => $sections['Liabilities']['subtypes'],
                'equity' => $sections['Equity']['subtypes'],
                'totalAssets' => (float)$sections['Assets']['total'],
                'totalLiabilities' => (float)$sections['Liabilities']['total'],
                'totalEquity' => (float)$sections['Equity']['total'],
                'totalLiabilitiesAndEquity' => (float)($sections['Liabilities']['total'] + $sections['Equity']['total']),
                'as_of_date' => $asOfDate,
            ]
        ]);
    }

    /**
     * Balance Sheet Export
     */
    public function balanceSheetExport(Request $request)
    {
        $response = $this->balanceSheet($request);
        $data = $response->getData(true)['data'];

        $user = Auth::user();
        $company = \App\Models\User::find($user->creatorId());
        $companyName = $company ? $company->name : 'Company';

        $pdf = Pdf::loadView('reports.balance-sheet', [
            'data' => $data,
            'companyName' => $companyName,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('balance-sheet-' . $data['as_of_date'] . '.pdf');
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

        $accounts = ChartOfAccount::with(['accountType'])->where('created_by', $creatorId)->get();

        $trialBalanceData = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $debitSum = JournalItem::join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
                ->where('journal_entries.created_by', $creatorId)
                ->where('journal_items.account', $account->id)
                ->whereBetween('journal_entries.date', [$startDate, $endDate])
                ->selectRaw("SUM(CAST(COALESCE(NULLIF(journal_items.debit, ''), '0') AS NUMERIC)) as total")
                ->value('total') ?? 0;

            $creditSum = JournalItem::join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
                ->where('journal_entries.created_by', $creatorId)
                ->where('journal_items.account', $account->id)
                ->whereBetween('journal_entries.date', [$startDate, $endDate])
                ->selectRaw("SUM(CAST(COALESCE(NULLIF(journal_items.credit, ''), '0') AS NUMERIC)) as total")
                ->value('total') ?? 0;

            $balance = $debitSum - $creditSum;

            if ($balance != 0) {
                $netDebit = $balance > 0 ? $balance : null;
                $netCredit = $balance < 0 ? abs($balance) : null;

                $trialBalanceData[] = [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->accountType?->name ?? 'Other',
                    'debit' => $netDebit ? (float) $netDebit : null,
                    'credit' => $netCredit ? (float) $netCredit : null,
                ];

                if ($netDebit) $totalDebit += $netDebit;
                if ($netCredit) $totalCredit += $netCredit;
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
     * Trial Balance Export (PDF)
     */
    public function trialBalanceExport(Request $request)
    {
        $response = $this->trialBalance($request);
        $data = $response->getData(true)['data'];

        $pdf = Pdf::loadView('reports.trial-balance', [
            'data' => $data,
        ])->setPaper('a4', 'landscape');

        $filename = 'trial-balance-' . $data['start_date'] . '-to-' . $data['end_date'] . '.pdf';

        return $pdf->download($filename);
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
