<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BankAccountController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\BillPaymentController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\ChartOfAccountController;
use App\Http\Controllers\Api\ChartOfAccountTypeController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\ContractTypeController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\CreditNoteController;
use App\Http\Controllers\Api\CustomFieldController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DebitNoteController;
use App\Http\Controllers\Api\EmailTemplateController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\JournalEntryController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\NotificationTemplateController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PdfExportController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\PlanRequestController;
use App\Http\Controllers\Api\ProductServiceCategoryController;
use App\Http\Controllers\Api\ProductServiceController;
use App\Http\Controllers\Api\ProductServiceUnitController;
use App\Http\Controllers\Api\ProductStockController;
use App\Http\Controllers\Api\ProposalController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReferralProgramController;
use App\Http\Controllers\Api\RetainerController;
use App\Http\Controllers\Api\RevenueController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\TaxController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UsersLogController;
use App\Http\Controllers\Api\VenderController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\BenefitPaymentController;
use App\Http\Controllers\Api\AiTemplateController;
use App\Http\Controllers\Api\POController;
use App\Http\Controllers\Api\WriteCheckController;


// Public routes
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Accountify API is running',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Authentication routes (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {

    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // User Management
    Route::apiResource('users', UserController::class);

    // Company Management (Super Admin)
    Route::apiResource('companies', CompanyController::class);

    // Role & Permission Management
    Route::apiResource('roles', RoleController::class);
    Route::get('/permissions', [RoleController::class, 'permissions']);

    // Customer Management
    Route::apiResource('customers', CustomerController::class);

    // Vendor Management
    Route::apiResource('venders', VenderController::class);

    // Invoice Management
    Route::apiResource('invoices', InvoiceController::class);

    // Bill Management
    Route::apiResource('bills', BillController::class);
    Route::get('bills/{bill}/payments', [BillPaymentController::class, 'index']);
    Route::post('bills/{bill}/payments', [BillPaymentController::class, 'store']);
    Route::delete('bills/{bill}/payments/{payment}', [BillPaymentController::class, 'destroy']);

    // Product & Service Management
    Route::apiResource('products', ProductServiceController::class);
    Route::apiResource('product-categories', ProductServiceCategoryController::class);
    Route::apiResource('product-units', ProductServiceUnitController::class);

    // Chart of Accounts
    Route::apiResource('chart-of-accounts', ChartOfAccountController::class);

    // Payments
    Route::apiResource('payments', PaymentController::class);

    // Expenses
    Route::apiResource('expenses', ExpenseController::class);

    // Purchase Orders
    Route::apiResource('purchase-orders', POController::class);

    // Write Checks
    Route::apiResource('write-checks', WriteCheckController::class);

    // Revenues
    Route::apiResource('revenues', RevenueController::class);

    // Plans
    Route::apiResource('plans', PlanController::class);

    // Tax Management
    Route::apiResource('taxes', TaxController::class);

    // Bank Account Management
    Route::apiResource('bank-accounts', BankAccountController::class);

    // Transfer Management
    Route::apiResource('transfers', TransferController::class);

    // Credit Note Management
    Route::apiResource('credit-notes', CreditNoteController::class);

    // Debit Note Management
    Route::apiResource('debit-notes', DebitNoteController::class);

    // Proposal Management
    Route::apiResource('proposals', ProposalController::class);

    // Retainer Management
    Route::apiResource('retainers', RetainerController::class);

    // Asset Management
    Route::apiResource('assets', AssetController::class);

    // Contract Management
    Route::apiResource('contracts', ContractController::class);

    // Custom Field Management
    Route::apiResource('custom-fields', CustomFieldController::class);

    // Email Template Management
    Route::apiResource('email-templates', EmailTemplateController::class);

    // Journal Entry Management
    Route::apiResource('journal-entries', JournalEntryController::class);

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);

    // Reports
    Route::get('reports/income-summary', [ReportController::class, 'incomeSummary']);
    Route::get('reports/expense-summary', [ReportController::class, 'expenseSummary']);
    Route::get('reports/income-vs-expense', [ReportController::class, 'incomeVsExpense']);
    Route::get('reports/profit-loss', [ReportController::class, 'profitLoss']);
    Route::get('reports/balance-sheet', [ReportController::class, 'balanceSheet']);
    Route::get('reports/trial-balance', [ReportController::class, 'trialBalance']);
    Route::get('reports/account-statement', [ReportController::class, 'accountStatement']);

    // HIGH PRIORITY FEATURES

    // Budgets
    Route::apiResource('budgets', BudgetController::class);

    // Transactions
    Route::apiResource('transactions', TransactionController::class);

    // Product Stock / Inventory
    Route::get('product-stock/summary', [ProductStockController::class, 'summary']);
    Route::apiResource('product-stock', ProductStockController::class);

    // Chart of Account Types
    Route::apiResource('chart-of-account-types', ChartOfAccountTypeController::class);

    // System Settings
    Route::get('settings/all', [SystemController::class, 'getSettings']);
    Route::get('settings/{name}', [SystemController::class, 'getSetting']);
    Route::post('settings/bulk', [SystemController::class, 'bulkUpdate']);
    Route::apiResource('settings', SystemController::class)->only(['index', 'store', 'destroy']);

    // Permissions
    Route::get('permissions/all', [PermissionController::class, 'all']);
    Route::post('permissions/assign-to-role', [PermissionController::class, 'assignToRole']);
    Route::apiResource('permissions', PermissionController::class);

    // MEDIUM PRIORITY FEATURES

    // Coupons
    Route::post('coupons/validate', [CouponController::class, 'validateCoupon']);
    Route::apiResource('coupons', CouponController::class);

    // Plan Requests
    Route::post('plan-requests/{id}/approve', [PlanRequestController::class, 'approve']);
    Route::apiResource('plan-requests', PlanRequestController::class);

    // Orders
    Route::post('orders/{id}/refund', [OrderController::class, 'refund']);
    Route::apiResource('orders', OrderController::class);

    // Contract Types
    Route::apiResource('contract-types', ContractTypeController::class);

    // Notification Templates
    Route::get('notification-templates/slug/{slug}', [NotificationTemplateController::class, 'getBySlug']);
    Route::post('notification-templates/{id}/language', [NotificationTemplateController::class, 'updateLanguage']);
    Route::apiResource('notification-templates', NotificationTemplateController::class);

    // Language / Localization
    Route::get('languages', [LanguageController::class, 'index']);
    Route::get('languages/{code}', [LanguageController::class, 'getTranslations']);
    Route::post('languages', [LanguageController::class, 'store']);
    Route::post('languages/change', [LanguageController::class, 'changeLanguage']);

    // Referral Program
    Route::get('referrals', [ReferralProgramController::class, 'index']);
    Route::get('referral-settings', [ReferralProgramController::class, 'settings']);
    Route::post('referral-settings', [ReferralProgramController::class, 'settings']);

    // Webhooks
    Route::apiResource('webhooks', WebhookController::class)->only(['index', 'store', 'destroy']);

    // User Logs
    Route::get('users-logs', [UsersLogController::class, 'index']);
    Route::delete('users-logs/{id}', [UsersLogController::class, 'destroy']);

    // PDF Exports
    Route::get('pdf/invoice/{id}', [PdfExportController::class, 'invoice']);
    Route::get('pdf/bill/{id}', [PdfExportController::class, 'bill']);
    Route::get('pdf/proposal/{id}', [PdfExportController::class, 'proposal']);
    Route::get('pdf/retainer/{id}', [PdfExportController::class, 'retainer']);

    // AI Templates
    Route::post('ai-generate', [AiTemplateController::class, 'generate']);

    // Payments Providers (Placeholders)
    Route::post('benefit-payment/invoice/{id}', [BenefitPaymentController::class, 'invoicePayWithBenefit']);
    Route::post('benefit-payment/retainer/{id}', [BenefitPaymentController::class, 'retainerPayWithBenefit']);
});

// Payment Gateway Callbacks
Route::any('benefit-payment/callback', [BenefitPaymentController::class, 'call_back']);

