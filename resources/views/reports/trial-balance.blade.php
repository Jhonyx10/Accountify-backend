<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Trial Balance</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #333;
            font-size: 11px;
            margin: 0;
            padding: 30px;
        }
        .header {
            width: 100%;
            margin-bottom: 40px;
        }
        .header table {
            width: 100%;
            border: none;
            margin: 0;
        }
        .header td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .header-label {
            color: #7f8c8d;
            font-size: 10px;
            margin-bottom: 4px;
        }
        .header-value {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table thead th {
            background-color: #f8f9fa;
            color: #333;
            padding: 15px 10px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            border: none;
        }
        .data-table tbody td {
            padding: 15px 10px;
            border-bottom: 1px solid #f4f4f4;
            font-size: 11px;
        }
        .account-name {
            color: #d9534f;
        }
        .w-50 { width: 50%; }
        .w-25 { width: 25%; }
        .text-center { text-align: center; }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td style="width: 50%;">
                    <div class="header-label">Report :</div>
                    <div class="header-value">Trial Balance Summary</div>
                </td>
                <td style="width: 50%;">
                    <div class="header-label">Duration :</div>
                    <div class="header-value">{{ $data['start_date'] }} to {{ $data['end_date'] }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th class="w-50">ACCOUNT NAME</th>
                <th class="w-25">DEBIT TOTAL</th>
                <th class="w-25">CREDIT TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['accounts'] as $account)
                <tr>
                    <td class="account-name">{{ $account['name'] }}</td>
                    <td>
                        @if($account['debit'])
                            &#8369;{{ number_format($account['debit'], 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($account['credit'])
                            &#8369;{{ number_format($account['credit'], 2) }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="padding: 20px; color: #999;">
                        No accounts with balances found for the selected period.
                    </td>
                </tr>
            @endforelse
            <tr>
                <td>Total</td>
                <td>&#8369;{{ number_format($data['total_debit'], 2) }}</td>
                <td>&#8369;{{ number_format($data['total_credit'], 2) }}</td>
            </tr>
        </tbody>
    </table>

</body>
</html>
