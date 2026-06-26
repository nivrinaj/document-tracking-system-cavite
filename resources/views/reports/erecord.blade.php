<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 14mm 10mm 16mm 10mm; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 9px; color: #111; margin: 0; }
        .head { text-align: center; margin-bottom: 8px; }
        .org { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; }
        .title { font-size: 15px; font-weight: bold; margin-top: 2px; }
        .sub { font-size: 9px; color: #444; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 0.5px solid #999; padding: 3px 4px; vertical-align: top; }
        thead th { background: #eee; font-size: 8.5px; text-transform: uppercase; letter-spacing: .3px; }
        td.num, th.num { text-align: right; }
        td.ctr, th.ctr { text-align: center; }
        tfoot td { font-weight: bold; background: #f5f5f5; }
        .foot { position: fixed; bottom: -8mm; left: 0; right: 0; font-size: 7.5px; color: #777; }
        .pf:after { content: counter(page) " of " counter(pages); }
    </style>
</head>
<body>
    @php
        $fundLabel = $fund ? $fund->name.' ('.$fund->reportCode().')' : '';
        $fmt = fn ($d) => $d->format('M j, Y'.($d->format('H:i') !== '00:00' ? ' g:i A' : ''));
        if ($from && $to) {
            $period = $fmt($from).' – '.$fmt($to);
        } elseif ($from) {
            $period = 'From '.$fmt($from);
        } elseif ($to) {
            $period = 'Up to '.$fmt($to);
        } else {
            $period = 'All dates';
        }
    @endphp

    <div class="head">
        @if($org)<div class="org">{{ $org }}</div>@endif
        <div class="title">{{ $reportTitle }}</div>
        <div class="sub">{{ $documentType }} &middot; Fund: {{ $fundLabel }} &middot; {{ $period }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date Received</th>
                <th class="ctr">DV #</th>
                <th>OBR No.</th>
                <th>Responsibility Center</th>
                <th class="ctr">Fund</th>
                <th>Payee</th>
                <th class="ctr">Nature</th>
                <th>Particulars</th>
                <th class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $doc)
                @php
                    $rc = $doc->responsibilityCenter;
                    $rcStr = $rc ? trim(($rc->code ? $rc->code.'/' : '').$rc->name) : ($doc->rc_code ?: '');
                    $nature = $natureCodes[$doc->nature_of_transaction] ?? $doc->nature_of_transaction;
                @endphp
                <tr>
                    <td>{{ optional($doc->created_at)->format('j-M') }}</td>
                    <td class="ctr">{{ $doc->dvNumber() }}</td>
                    <td>{{ $doc->obr_no }}</td>
                    <td>{{ $rcStr }}</td>
                    <td class="ctr">{{ optional($doc->fund)->reportCode() }}</td>
                    <td>{{ $doc->title }}</td>
                    <td class="ctr">{{ $nature }}</td>
                    <td>{{ $doc->description }}</td>
                    <td class="num">{{ $doc->amount !== null ? number_format($doc->amount, 2) : '' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center; padding:14px; color:#777;">No documents found for these filters.</td></tr>
            @endforelse
        </tbody>
        @if($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="8" style="text-align:right;">TOTAL</td>
                    <td class="num">{{ number_format($total, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="foot">
        <table style="border:none;"><tr style="border:none;">
            <td style="border:none; text-align:left;">{{ $appName }} &middot; Generated {{ $generatedAt->format('M d, Y g:i A') }}</td>
            <td style="border:none; text-align:right;" class="pf">Page </td>
        </tr></table>
    </div>
</body>
</html>
