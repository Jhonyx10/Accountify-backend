<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balance Sheet</title>
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
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .report-date {
            font-size: 13px;
            color: #95a5a6;
        }
        .section-title {
            font-size: 15px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 8px;
            border-bottom: 2px solid #eee;
            padding-bottom: 5px;
            text-transform: uppercase;
        }
        .text-primary { color: #3498db; }
        .text-warning { color: #f39c12; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        th, td {
            padding: 6px 10px;
            text-align: left;
        }
        .text-right { text-align: right; }
        .w-75 { width: 75%; }
        .w-25 { width: 25%; }

        /* L1: Subtype (e.g. "Current Assets") */
        .subtype-name {
            font-weight: bold;
            font-size: 13px;
            color: #333;
            padding: 10px 10px 4px 10px;
            background-color: #f4f6f8;
            margin-top: 8px;
        }

        /* L2: Group (e.g. "Bank & Cash") */
        .group-name {
            font-weight: bold;
            font-size: 12px;
            color: #555;
            padding: 6px 10px 3px 24px;
        }

        /* L3: Account rows */
        .account-row td {
            padding-left: 40px;
            color: #555;
            font-size: 12px;
        }

        .main-total-row {
            font-weight: bold;
            font-size: 14px;
        }
        .main-total-row td {
            padding-top: 12px;
            padding-bottom: 12px;
        }
        .main-total-value {
            border-top: 2px solid #333;
            border-bottom: 2px double #333;
        }
        .sub-total-row td {
            border-top: 1px solid #ddd;
            font-weight: bold;
        }
        .border-t { border-top: 1px solid #ddd; }
        .mb-20 { margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="report-title">Balance Sheet</div>
        <div class="report-date">As of {{ date('F j, Y', strtotime($data['as_of_date'])) }}</div>
    </div>

    <!-- ASSETS -->
    <div class="section-title text-primary">Assets</div>

    @foreach($data['assets'] as $subtype)
        <div class="subtype-name">{{ $subtype['name'] }}</div>
        @foreach($subtype['groups'] as $group)
            <div class="group-name">{{ $group['name'] }}</div>
            <table>
                <tbody>
                    @foreach($group['items'] as $item)
                        <tr class="account-row">
                            <td class="w-75">{{ $item['name'] }}</td>
                            <td class="w-25 text-right">&#8369;{{ number_format($item['balance'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endforeach

    <table>
        <tr class="main-total-row">
            <td class="w-75">Total Assets</td>
            <td class="w-25 text-right main-total-value">&#8369;{{ number_format($data['totalAssets'], 2) }}</td>
        </tr>
    </table>

    <div class="mb-20"></div>

    <!-- LIABILITIES & EQUITY -->
    <div class="section-title text-warning">Liabilities &amp; Equity</div>

    <!-- Liabilities -->
    @foreach($data['liabilities'] as $subtype)
        <div class="subtype-name">{{ $subtype['name'] }}</div>
        @foreach($subtype['groups'] as $group)
            <div class="group-name">{{ $group['name'] }}</div>
            <table>
                <tbody>
                    @foreach($group['items'] as $item)
                        <tr class="account-row">
                            <td class="w-75">{{ $item['name'] }}</td>
                            <td class="w-25 text-right">&#8369;{{ number_format($item['balance'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endforeach

    <table>
        <tr class="main-total-row">
            <td class="w-75">Total Liabilities</td>
            <td class="w-25 text-right border-t">&#8369;{{ number_format($data['totalLiabilities'], 2) }}</td>
        </tr>
    </table>

    <!-- Equity -->
    @foreach($data['equity'] as $subtype)
        <div class="subtype-name">{{ $subtype['name'] }}</div>
        @foreach($subtype['groups'] as $group)
            <div class="group-name">{{ $group['name'] }}</div>
            <table>
                <tbody>
                    @foreach($group['items'] as $item)
                        <tr class="account-row">
                            <td class="w-75">{{ $item['name'] }}</td>
                            <td class="w-25 text-right">&#8369;{{ number_format($item['balance'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endforeach

    <table>
        <tr class="main-total-row">
            <td class="w-75">Total Equity</td>
            <td class="w-25 text-right border-t">&#8369;{{ number_format($data['totalEquity'], 2) }}</td>
        </tr>
    </table>

    <div class="mb-20"></div>

    <table>
        <tr class="main-total-row">
            <td class="w-75 text-warning">Total Liabilities &amp; Equity</td>
            <td class="w-25 text-right main-total-value">&#8369;{{ number_format($data['totalLiabilitiesAndEquity'], 2) }}</td>
        </tr>
    </table>

</body>
</html>
