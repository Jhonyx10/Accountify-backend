<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_id }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.5;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            padding: 30px;
            box-sizing: border-box;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #ddd;
            padding-bottom: 20px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .header-left {
            float: left;
            width: 50%;
        }
        .header-right {
            float: right;
            width: 50%;
            text-align: right;
        }
        .brand-logo {
            max-width: 150px;
            max-height: 60px;
        }
        .text-muted {
            color: #777;
        }
        .info-section {
            width: 100%;
            margin-bottom: 30px;
            overflow: hidden;
        }
        .info-left {
            float: left;
            width: 50%;
        }
        .info-right {
            float: right;
            width: 50%;
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table th, table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        table th {
            background-color: #f8f8f8;
            font-weight: bold;
        }
        table .text-right {
            text-align: right;
        }
        table .text-center {
            text-align: center;
        }
        .totals {
            width: 40%;
            float: right;
            margin-bottom: 30px;
        }
        .totals table th, .totals table td {
            padding: 8px 10px;
            border: none;
            border-bottom: 1px solid #eee;
        }
        .totals table .total-row th, .totals table .total-row td {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 16px;
            color: {{ $settings['primary_color'] ?? '#000000' }};
        }
        .notes {
            clear: both;
            background-color: #f9f9f9;
            padding: 15px;
            border-left: 4px solid {{ $settings['primary_color'] ?? '#333' }};
            margin-top: 20px;
        }
        .footer {
            clear: both;
            text-align: center;
            font-size: 12px;
            color: #fff;
            background-color: {{ $settings['primary_color'] ?? '#333' }};
            padding: 15px;
            margin-top: 50px;
            border-radius: 5px;
        }
        h2 {
            margin-top: 0;
            color: {{ $settings['primary_color'] ?? '#333' }};
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <!-- Branding Setting -->
                @if(isset($settings['company_logo']) && !empty($settings['company_logo']))
                    <img src="{{ public_path('storage/' . $settings['company_logo']) }}" alt="Logo" class="brand-logo">
                @else
                    <h2>{{ $settings['company_name'] ?? 'Company Name' }}</h2>
                @endif
                <p class="text-muted">
                    {!! nl2br(e($settings['company_address'] ?? '123 Business Rd.\nCity, State 12345\nContact: 123-456-7890')) !!}
                </p>
            </div>
            <div class="header-right">
                <h1 style="color: {{ $settings['primary_color'] ?? '#333' }};">INVOICE</h1>
                <p class="text-muted">
                    <strong>Invoice #:</strong> INV-{{ str_pad($invoice->invoice_id, 5, '0', STR_PAD_LEFT) }}<br>
                    <strong>Issue Date:</strong> {{ \Carbon\Carbon::parse($invoice->issue_date)->format('F d, Y') }}<br>
                    <strong>Due Date:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('F d, Y') }}<br>
                    <strong>Status:</strong> 
                    @if($invoice->status == 2) Paid 
                    @elseif($invoice->status == 1) Sent 
                    @else Draft 
                    @endif
                </p>
            </div>
        </div>

        <!-- Issue To / Billed To -->
        <div class="info-section">
            <div class="info-left">
                <h3>Billed To:</h3>
                <p>
                    <strong>{{ $invoice->customer->name ?? 'N/A' }}</strong><br>
                    {{ $invoice->customer->billing_address ?? '' }}<br>
                    {{ $invoice->customer->billing_city ?? '' }} {{ $invoice->customer->billing_zip ?? '' }}<br>
                    {{ $invoice->customer->email ?? '' }}
                </p>
            </div>
        </div>

        <!-- Line Items -->
        <table>
            <thead>
                <tr>
                    <th width="40%">Item Description</th>
                    <th width="15%" class="text-center">Qty</th>
                    <th width="20%" class="text-right">Price</th>
                    <th width="25%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->products as $item)
                <tr>
                    <td>
                        <strong>{{ $item->description }}</strong>
                        @if($item->tax && $item->tax != '0')
                            <br><small class="text-muted">Tax: {{ $item->tax }}%</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">₱{{ number_format($item->price, 2) }}</td>
                    <td class="text-right">₱{{ number_format($item->quantity * $item->price * (1 + $item->tax / 100), 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table>
                <tr>
                    <th class="text-right">Subtotal</th>
                    <td class="text-right">₱{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <th class="text-right">Tax</th>
                    <td class="text-right">₱{{ number_format($invoice->total_tax, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <th class="text-right">Grand Total</th>
                    <td class="text-right">₱{{ number_format($invoice->grand_total, 2) }}</td>
                </tr>
            </table>
        </div>

        <div style="clear: both;"></div>

        <!-- Notes -->
        @if($invoice->notes)
        <div class="notes">
            <strong>Notes:</strong><br>
            {!! nl2br(e($invoice->notes)) !!}
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            {{ $settings['company_name'] ?? 'Accountify' }} | {{ $settings['company_email'] ?? 'support@accountify.com' }}
            <br>
            {{ $settings['footer_notes'] ?? 'Thank you for your business!' }}
        </div>
    </div>
</body>
</html>
