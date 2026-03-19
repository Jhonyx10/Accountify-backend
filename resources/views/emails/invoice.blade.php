<!DOCTYPE html>
<html>
<head>
    <title>Invoice from {{ config('app.name') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Dear {{ $invoice->customer->name ?? 'Customer' }},</p>
    
    <p>Please find attached the invoice <strong>#INV-{{ str_pad($invoice->invoice_id, 5, '0', STR_PAD_LEFT) }}</strong> for your recent purchase.</p>

    <p><strong>Due Date:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('F d, Y') }}<br>
       <strong>Amount Due:</strong> ₱{{ number_format($invoice->grand_total, 2) }}
    </p>

    <p>If you have any questions about this invoice, please don't hesitate to reach out.</p>

    <p>Thank you for your business!</p>

    <p>Best regards,<br>
    {{ config('app.name') }} Team</p>
</body>
</html>
