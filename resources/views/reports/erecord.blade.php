<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 12mm 8mm 14mm 8mm; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 9px; color: #111; margin: 0; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .head { text-align: center; margin-bottom: 6px; }
        .org { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; }
        .office { font-size: 10px; font-weight: bold; margin-top: 1px; }
        .title { font-size: 15px; font-weight: bold; margin-top: 3px; }
        .sub { font-size: 9px; color: #444; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 0.5px solid #999; padding: 3px 4px; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
        thead th { background: #eee; font-size: 8.5px; text-transform: uppercase; letter-spacing: .3px; }
        tr.subtotal td { font-weight: bold; background: #f7f7f7; }
        tr.grand td { font-weight: bold; background: #e9e9e9; font-size: 10px; }
    </style>
</head>
<body>
    @php
        $a = ($align ?? []) + ['date'=>'center','dv'=>'center','obr'=>'left','rc'=>'left','fund'=>'center','payee'=>'left','nature'=>'center','particulars'=>'left','amount'=>'right'];
        $w = ['date'=>'7%','dv'=>'5%','obr'=>'10%','rc'=>'13%','fund'=>'5%','payee'=>'19%','nature'=>'6%','particulars'=>'25%','amount'=>'10%'];
        $fundLabel = $fund ? $fund->name.' ('.$fund->reportCode().')' : '';
        $fmt = fn ($d) => $d->format('M j, Y'.($d->format('H:i') !== '00:00' ? ' g:i A' : ''));
        $period = ($from && $to) ? $fmt($from).' – '.$fmt($to) : ($from ? 'From '.$fmt($from) : ($to ? 'Up to '.$fmt($to) : 'All dates'));
        $hosp = ($hospital ?? 'exclude') === 'only' ? ' · Hospital only' : (($hospital ?? '') === 'include' ? ' · incl. Hospital' : '');
        $pages = $rows->chunk($perPage ?? 16);
    @endphp

    @forelse($pages as $chunk)
        <div class="page">
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
                    @foreach($chunk as $doc)
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
                    @endforeach
                    <tr class="subtotal">
                        <td colspan="8" style="text-align:right;">Page subtotal</td>
                        <td style="text-align:{{ $a['amount'] }}">{{ number_format($chunk->sum('amount'), 2) }}</td>
                    </tr>
                    @if($loop->last)
                        <tr class="grand">
                            <td colspan="8" style="text-align:right;">GRAND TOTAL</td>
                            <td style="text-align:{{ $a['amount'] }}">{{ number_format($total, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @empty
        <div class="head">
            @if($org)<div class="org">{{ $org }}</div>@endif
            @if(!empty($officeName))<div class="office">{{ $officeName }}</div>@endif
            <div class="title">{{ $reportTitle }}</div>
            <div class="sub">{{ $documentType }} &middot; Fund: {{ $fundLabel }} &middot; {{ $period }}{{ $hosp }}</div>
        </div>
        <p style="text-align:center; color:#777; padding:16px;">No documents found for these filters.</p>
    @endforelse

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $size = 7.5;
            $color = array(0.45, 0.45, 0.45);
            $w = $pdf->get_width();
            $h = $pdf->get_height();
            $pdf->page_text($w - 95, $h - 22, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, $size, $color);
            $pdf->page_text(20, $h - 22, "{{ addslashes($appName) }} · Generated {{ addslashes($generatedAt->format('M d, Y g:i A')) }}", $font, $size, $color);
        }
    </script>
</body>
</html>
