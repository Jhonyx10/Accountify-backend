<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Bill;
use App\Models\Proposal;
use App\Models\Retainer;
use Barryvdh\DomPDF\Facade\Pdf; // Suggesting DOMPDF

class PdfExportController extends Controller
{
    /**
     * Download or view Invoice PDF
     */
    public function invoice(Request $request, $id)
    {
        $user = $request->user();

        $invoice = Invoice::where('created_by', $user->creatorId())
            ->with(['payments', 'items', 'customer'])
            ->findOrFail($id);

        $data = ['invoice' => $invoice];

        // This requires installing barryvdh/laravel-dompdf
        // $pdf = Pdf::loadView('pdf.invoice', $data);
        // return $pdf->download('invoice-' . $invoice->invoice_id . '.pdf');

        return response()->json([
            'success' => false,
            'message' => 'PDF generation logic pending DOMPDF view creation',
            'data' => $data
        ], 501);
    }

    /**
     * Download or view Bill PDF
     */
    public function bill(Request $request, $id)
    {
        $user = $request->user();

        $bill = Bill::where('created_by', $user->creatorId())
            ->with(['payments', 'items', 'vendor'])
            ->findOrFail($id);

        $data = ['bill' => $bill];

        // $pdf = Pdf::loadView('pdf.bill', $data);
        // return $pdf->download('bill-' . $bill->bill_id . '.pdf');

        return response()->json([
            'success' => false,
            'message' => 'PDF generation logic pending DOMPDF view creation',
            'data' => $data
        ], 501);
    }

    /**
     * Download or view Proposal PDF
     */
    public function proposal(Request $request, $id)
    {
        $user = $request->user();

        $proposal = Proposal::where('created_by', $user->creatorId())
            ->with(['items', 'customer'])
            ->findOrFail($id);

        $data = ['proposal' => $proposal];

        // $pdf = Pdf::loadView('pdf.proposal', $data);
        // return $pdf->download('proposal-' . $proposal->proposal_id . '.pdf');

        return response()->json([
            'success' => false,
            'message' => 'PDF generation logic pending DOMPDF view creation',
            'data' => $data
        ], 501);
    }

    /**
     * Download or view Retainer PDF
     */
    public function retainer(Request $request, $id)
    {
        $user = $request->user();

        $retainer = Retainer::where('created_by', $user->creatorId())
            ->with(['items', 'customer'])
            ->findOrFail($id);

        $data = ['retainer' => $retainer];

        // $pdf = Pdf::loadView('pdf.retainer', $data);
        // return $pdf->download('retainer-' . $retainer->retainer_id . '.pdf');

        return response()->json([
            'success' => false,
            'message' => 'PDF generation logic pending DOMPDF view creation',
            'data' => $data
        ], 501);
    }
}
