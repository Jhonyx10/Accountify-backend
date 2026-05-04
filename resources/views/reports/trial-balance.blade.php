<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Trial Balance</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #333;
            font-size: 13px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 20px;
        }
        .report-title {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .report-date {
            font-size: 13px;
            color: #7f8c8d;
        }
        .balance-status {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .balanced { background-color: #d5f5e3; color: #1e8449; }
        .unbalanced { background-color: #fadbd8; color: #922b21; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        thead th {
            background-color: #2c3e50;
            color: #fff;
            padding: 10px 12px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        thead th.text-right { text-align: right; }

        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        tbody td {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .w-10 { width: 10%; }
        .w-15 { width: 15%; }
        .w-40 { width: 40%; }
        .w-35 { width: 35%; }
        .w-20 { width: 20%; }

        .type-chip {
            background-color: #eaf0fb;
            color: #2980b9;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 10px;
        }

        tfoot td {
            padding: 12px;
            font-weight: bold;
            font-size: 13px;
            border-top: 2px solid #2c3e50;
            background-color: #f4f6f8;
        }
        .total-debit { color: #1a5276; }
        .total-credit { color: #1a5276; }
        .total-label { text-align: right; }

        .footer-note {
            margin-top: 20px;
            font-size: 11px;
            color: #95a5a6;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="report-title">Trial Balance</div>
        <div class="report-date">
            Period: {{ date('F j, Y', strtotime($data['start_date'])) }} &ndash; {{ date('F j, Y', strtotime($data['end_date'])) }}
        </div>

    </div>

    <table>
        <thead>
            <tr>
                <th class="w-45">Account Name</th>
                <th class="w-15">Type</th>
                <th class="w-20 text-right">Debit</th>
                <th class="w-20 text-right">Credit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['accounts'] as $account)
                <tr>
                    <td>{{ $account['name'] }}</td>
                    <td><span class="type-chip">{{ $account['type'] }}</span></td>
                    <td class="text-right">
                        @if($account['debit'])
                            &#8369;{{ number_format($account['debit'], 2) }}
                        @else
                            &mdash;
                        @endif
                    </td>
                    <td class="text-right">
                        @if($account['credit'])
                            &#8369;{{ number_format($account['credit'], 2) }}
                        @else
                            &mdash;
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center" style="padding: 20px; color: #999;">
                        No accounts with balances found for the selected period.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="total-label">Totals</td>
                <td class="text-right total-debit">&#8369;{{ number_format($data['total_debit'], 2) }}</td>
                <td class="text-right total-credit">&#8369;{{ number_format($data['total_credit'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer-note">
        Generated on {{ date('F j, Y \a\t g:i A') }}
    </div>

</body>
</html>
