<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balance Sheet</title>
    <style>
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            color: #000;
            font-size: 11px;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .report-title {
            font-size: 20px;
            font-weight: bold;
            color: #000;
            margin-bottom: 10px;
        }
        .report-date {
            font-size: 12px;
            color: #666;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ccc;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 6px 10px;
            text-align: left;
        }
        th:first-child, td:first-child {
            width: 80%;
        }
        th:last-child, td:last-child {
            width: 20%;
        }
        .text-right { text-align: right; }
        
        .section-header td {
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .l1-row td {
            font-weight: bold;
        }
        .l2-row td {
            font-weight: bold;
        }
        .l2-row td:first-child {
            padding-left: 20px;
        }
        
        .l3-row td {
            font-weight: normal;
        }
        .l3-row td:first-child {
            padding-left: 40px;
        }

        .total-l1-row td {
            font-weight: bold;
        }
        
        .final-total-row {
            color: #d32f2f;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        .final-total-row td {
            border-top: 1.5px solid #d32f2f !important;
            border-bottom: 1.5px solid #d32f2f !important;
            border-left: 1.5px solid #d32f2f !important;
            border-right: 1.5px solid #d32f2f !important;
        }
        
        .mb-20 { margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="report-title">Balance Sheet</div>
        @php
            $date = \Carbon\Carbon::parse($data['as_of_date']);
            $startOfMonth = $date->copy()->startOfMonth()->format('F j');
            $endOfMonth = $date->format('j, Y');
            $dateString = "As of {$startOfMonth} - {$endOfMonth}";
        @endphp
        <div class="report-date">{{ $dateString }}</div>
    </div>

    <table>
        <tbody>
            <!-- ASSETS -->
            <tr class="section-header">
                <td>ASSETS</td>
                <td></td>
            </tr>
            
            @foreach($data['assets'] as $subtype)
                <tr class="l1-row">
                    <td>{{ $subtype['name'] }}</td>
                    <td class="text-right">{{ number_format($subtype['total'], 2) }}</td>
                </tr>
                @foreach($subtype['groups'] as $group)
                    <tr class="l2-row">
                        <td>{{ $group['name'] }}</td>
                        <td class="text-right">{{ number_format($group['total'], 2) }}</td>
                    </tr>
                    @foreach($group['items'] as $item)
                        <tr class="l3-row">
                            <td>{{ $item['name'] }}</td>
                            <td class="text-right">{{ number_format($item['balance'], 2) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
            
            <tr class="final-total-row">
                <td>TOTAL ASSETS</td>
                <td class="text-right">{{ number_format($data['totalAssets'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="mb-20"></div>

    <table>
        <tbody>
            <!-- LIABILITIES & EQUITY -->
            <!-- LIABILITIES -->
            <tr class="section-header">
                <td>LIABILITIES</td>
                <td></td>
            </tr>
            
            @foreach($data['liabilities'] as $subtype)
                <tr class="l1-row">
                    <td>{{ $subtype['name'] }}</td>
                    <td class="text-right">{{ number_format($subtype['total'], 2) }}</td>
                </tr>
                @foreach($subtype['groups'] as $group)
                    <tr class="l2-row">
                        <td>{{ $group['name'] }}</td>
                        <td class="text-right">{{ number_format($group['total'], 2) }}</td>
                    </tr>
                    @foreach($group['items'] as $item)
                        <tr class="l3-row">
                            <td>{{ $item['name'] }}</td>
                            <td class="text-right">{{ number_format($item['balance'], 2) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
            
            <!-- We will skip "Total Liabilities" separate row to match the section totals style unless needed -->
            @if(count($data['liabilities']) > 0)
            <tr class="total-l1-row">
                <td>Total Liabilities</td>
                <td class="text-right">{{ number_format($data['totalLiabilities'], 2) }}</td>
            </tr>
            @endif

            <!-- EQUITY -->
            <tr class="section-header">
                <td>EQUITY</td>
                <td></td>
            </tr>
            
            @foreach($data['equity'] as $subtype)
                <tr class="l1-row">
                    <td>{{ $subtype['name'] }}</td>
                    <td class="text-right">{{ number_format($subtype['total'], 2) }}</td>
                </tr>
                @foreach($subtype['groups'] as $group)
                    <tr class="l2-row">
                        <td>{{ $group['name'] }}</td>
                        <td class="text-right">{{ number_format($group['total'], 2) }}</td>
                    </tr>
                    @foreach($group['items'] as $item)
                        <tr class="l3-row">
                            <td>{{ $item['name'] }}</td>
                            <td class="text-right">{{ number_format($item['balance'], 2) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
            
            @if(count($data['equity']) > 0)
            <tr class="total-l1-row">
                <td>Total Equity</td>
                <td class="text-right">{{ number_format($data['totalEquity'], 2) }}</td>
            </tr>
            @endif

            <tr class="final-total-row">
                <td>TOTAL LIABILITIES AND EQUITY</td>
                <td class="text-right">{{ number_format($data['totalLiabilitiesAndEquity'], 2) }}</td>
            </tr>
        </tbody>
    </table>

</body>
</html>
