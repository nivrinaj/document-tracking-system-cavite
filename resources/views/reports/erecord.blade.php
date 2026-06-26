<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 12mm 8mm 14mm 8mm; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 9px; color: #111; margin: 0; }
        .head { text-align: center; margin-bottom: 8px; }
        .org { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; }
        .office { font-size: 10px; font-weight: bold; margin-top: 1px; }
        .title { font-size: 15px; font-weight: bold; margin-top: 3px; }
        .sub { font-size: 9px; color: #444; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 0.5px solid #999; padding: 3px 4px; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
        thead th { background: #eee; font-size: 8.5px; text-transform: uppercase; letter-spacing: .3px; }
        tfoot td { font-weight: bold; background: #f5f5f5; }
        .foot { position: fixed; bottom: -7mm; left: 0; right: 0; font-size: 7.5px; color: #777; }
        .pf:after { content: counter(page) " of " counter(pages); }
    </style>
</head>
<body>
    @php
        $a = ($align ?? []) + ['date'=>'center','dv'=>'center','obr'=>'left','rc'=>'left','fund'=>'center','payee'=>'left','nature'=>'center','particulars'=>'left','amount'=>'right'];
        $w = ['date'=>'7%','dv'=>'4%','obr'=>'9%','rc'=>'13%','fund'=>'5%','payee'=>'23%','nature'=>'6%','particulars'=>'19%','amount'=>'9%'];
        $fundLabel = $fund ? $fund->name.' ('.$fund->reportCode().')' : '';
        $fmt = fn ($d) => $d->format('M j, Y'.($d->format('H:i') !== '00:00' ? ' g:i A' : ''));
        $period = ($from && $to) ? $fmt($from).' – '.$fmt($to) : ($from ? 'From '.$fmt($from) : ($to ? 'Up to '.$fmt($to) : 'All dates'));
        $hosp = ($hospital ?? 'exclude') === 'only' ? ' · Hospital only' : (($hospital ?? '') === 'include' ? ' · incl. Hospital' : '');
    @endphp

    <div class="head">
        @if($org)<div class="org">{{ $org }}</div>@endif
        @if(!empty($officeName))<div class="office">{{ $officeName }}</div>@endif
        <div class="title">{{ $reportTitle }}</div>
        <div class="sub">{{ $documentType }} &middot; Fund: {{ $fundLabel }} &middot; {{ $period }}{{ $hosp }}</div>
    </div>

    <table>
        <colgroup>
            <col style="width:{{ $w['date'] }}"><col style="width:{{ $w['dv'] }}"><col style="width:{{ $w['obr'] }}">
            <col style="width:{{ $w['rc'] }}"><col style="width:{{ $w['fund'] }}"><col style="width:{{ $w['payee'] }}">
            <col style="width:{{ $w['nature'] }}"><col style="width:{{ $w['particulars'] }}"><col style="width:{{ $w['amount'] }}">
        </colgroup>
        <thead>
            <tr>
                <th style="text-align:{{ $a['date'] }}">Date Received</th>
                <th style="text-align:{{ $a['dv'] }}">DV #</th>
                <th style="text-align:{{ $a['obr'] }}">OBR No.</th>
                <th style="text-align:{{ $a['rc'] }}">Responsibility Center</th>
                <th style="text-align:{{ $a['fund'] }}">Fund</th>
                <th style="text-align:{{ $a['payee'] }}">Payee</th>
                <th style="text-align:{{ $a['nature'] }}">Nature</th>
                <th style="text-align:{{ $a['particulars'] }}">Particulars</th>
                <th style="text-align:{{ $a['amount'] }}">Amount</th>
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
                    <td style="text-align:{{ $a['date'] }}">{{ optional($doc->created_at)->format('j-M') }}</td>
                    <td style="text-align:{{ $a['dv'] }}">{{ $doc->dvNumber() }}</td>
                    <td style="text-align:{{ $a['obr'] }}">{{ $doc->obr_no }}</td>
                    <td style="text-align:{{ $a['rc'] }}">{{ $rcStr }}</td>
                    <td style="text-align:{{ $a['fund'] }}">{{ optional($doc->fund)->reportCode() }}</td>
                    <td style="text-align:{{ $a['payee'] }}">{{ $doc->title }}</td>
                    <td style="text-align:{{ $a['nature'] }}">{{ $nature }}</td>
                    <td style="text-align:{{ $a['particulars'] }}">{{ $doc->description }}</td>
                    <td style="text-align:{{ $a['amount'] }}">{{ $doc->amount !== null ? number_format($doc->amount, 2) : '' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center; padding:14px; color:#777;">No documents found for these filters.</td></tr>
            @endforelse
        </tbody>
        @if($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="8" style="text-align:right;">TOTAL</td>
                    <td style="text-align:{{ $a['amount'] }}">{{ number_format($total, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="foot">
        <table style="border:none;"><tr>
            <td style="border:none; text-align:left;">{{ $appName }} &middot; Generated {{ $generatedAt->format('M d, Y g:i A') }}</td>
            <td style="border:none; text-align:right;" class="pf">Page </td>
        </tr></table>
    </div>
</body>
</html>
