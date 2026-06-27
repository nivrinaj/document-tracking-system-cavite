<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }} - {{ optional($fund)->reportCode() }}{{ ($hospital ?? 'exclude') === 'only' ? '-H' : '' }}</title>
    <style>
        @page { margin: 10mm 6mm 10mm 6mm; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 8px; color: #111; margin: 0; }
        .page-break { page-break-after: always; }
        .head { margin-bottom: 4px; }
        .head-line { font-size: 9px; }
        .head-org { font-weight: bold; }
        .head-title { font-size: 10px; font-weight: bold; margin-top: 3px; }
        .head-fund { font-size: 9px; font-weight: bold; margin-top: 2px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 0.5px solid #000; padding: 2px 3px; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
        thead th { background: #f0f0f0; font-size: 7px; text-transform: uppercase; letter-spacing: .2px; text-align: center; vertical-align: middle; }
        tr.subtotal td { font-weight: bold; background: #f7f7f7; }
        tr.grand td { font-weight: bold; background: #e9e9e9; font-size: 9px; }
    </style>
</head>
<body>
    @php
        $a = ($align ?? []) + [
            'date_jev'=>'center','dv'=>'center','obr'=>'left','rc'=>'left','fund'=>'center',
            'payee'=>'left','nature'=>'center','particulars'=>'left','amount'=>'right',
            'date_review'=>'center','secretary'=>'center','releasing'=>'center',
            'days'=>'center','date_in'=>'center','date_out'=>'center',
        ];
        $cl = ($colLabels ?? []) + [
            'date_jev'=>'Date Received JEV','dv'=>'DV No.','obr'=>'OBR No.','rc'=>'RC','fund'=>'Fund',
            'payee'=>'Payee','nature'=>'Nature','particulars'=>'Particulars / Explanation','amount'=>'Amount',
            'date_review'=>'Date Received Review','secretary'=>'Received by Secretary',
            'releasing'=>'Received by Releasing Staff','days'=>'No. of Days','date_in'=>'Date In','date_out'=>'Date Out',
        ];
        // Widths: data cols get space, blank tracking cols are minimal
        $w = [
            'date_jev'=>'5.5%','dv'=>'10%','obr'=>'7%','rc'=>'6%','fund'=>'3.5%',
            'payee'=>'16%','nature'=>'4%','particulars'=>'19%','amount'=>'7%',
            'date_review'=>'5.5%','secretary'=>'4%','releasing'=>'4%',
            'days'=>'2.5%','date_in'=>'3%','date_out'=>'3%',
        ];
        $fundLabel = $fund ? strtoupper($fund->name).' - '.$fund->reportCode().' YEAR '.date('Y') : '';
        $pages = $rows->chunk($perPage ?? 16);
    @endphp

    @forelse($pages as $chunk)
        <div class="{{ $loop->last ? '' : 'page-break' }}">
            <div class="head">
                <div class="head-line">
                    <span class="head-org">{{ $org ?? '' }}</span>
                    @if(!empty($officeName)) - {{ $officeName }}@endif
                    @if(!empty($divisionName)) - {{ $divisionName }}@endif
                </div>
                <div class="head-title">{{ $reportTitle }} <span style="font-weight:normal;font-size:8px;margin-left:8px;">{{ $isoCode ?? '' }}</span></div>
                <div class="head-fund">{{ $fundLabel }}</div>
            </div>

            <table>
                <colgroup>
                    @foreach(['date_jev','dv','obr','rc','fund','payee','nature','particulars','amount','date_review','secretary','releasing','days','date_in','date_out'] as $c)
                        <col style="width:{{ $w[$c] }}">
                    @endforeach
                </colgroup>
                <thead>
                    <tr>
                        @foreach(['date_jev','dv','obr','rc','fund','payee','nature','particulars','amount','date_review','secretary','releasing','days','date_in','date_out'] as $c)
                            <th style="width:{{ $w[$c] }};text-align:center">{{ $cl[$c] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($chunk as $doc)
                        @php
                            $rc = $doc->responsibilityCenter;
                            $rcStr = $rc ? trim(($rc->code ? $rc->code.'/' : '').$rc->name) : ($doc->rc_code ?: '');
                            $nature = $natureCodes[$doc->nature_of_transaction] ?? $doc->nature_of_transaction;
                            $dateVal = ($dateSource ?? 'created') === 'received_by_division' && isset($receivedDates[$doc->id])
                                ? $receivedDates[$doc->id]
                                : $doc->created_at;
                        @endphp
                        <tr>
                            <td style="text-align:{{ $a['date_jev'] }}">{{ $dateVal ? $dateVal->format('d-M') : '' }}</td>
                            <td style="text-align:{{ $a['dv'] }}">{{ $doc->tracking_code }}</td>
                            <td style="text-align:{{ $a['obr'] }}">{{ $doc->obr_no }}</td>
                            <td style="text-align:{{ $a['rc'] }}">{{ $rcStr }}</td>
                            <td style="text-align:{{ $a['fund'] }}">{{ optional($doc->fund)->reportCode() }}</td>
                            <td style="text-align:{{ $a['payee'] }}">{{ $doc->title }}</td>
                            <td style="text-align:{{ $a['nature'] }}">{{ $nature }}</td>
                            <td style="text-align:{{ $a['particulars'] }}">{{ $doc->description }}</td>
                            <td style="text-align:{{ $a['amount'] }}">{{ $doc->amount !== null ? number_format($doc->amount, 2) : '' }}</td>
                            <td style="text-align:{{ $a['date_review'] }}">{{ $dateVal ? $dateVal->format('d-M') : '' }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endforeach
                    @if(!empty($showTotals))
                    <tr class="subtotal">
                        <td colspan="8" style="text-align:right;">Page subtotal</td>
                        <td style="text-align:{{ $a['amount'] }}">{{ number_format($chunk->sum('amount'), 2) }}</td>
                        <td colspan="6"></td>
                    </tr>
                    @if($loop->last)
                        <tr class="grand">
                            <td colspan="8" style="text-align:right;">GRAND TOTAL</td>
                            <td style="text-align:{{ $a['amount'] }}">{{ number_format($total, 2) }}</td>
                            <td colspan="6"></td>
                        </tr>
                    @endif
                    @endif
                </tbody>
            </table>
        </div>
    @empty
        <div class="head">
            <div class="head-line">
                <span class="head-org">{{ $org ?? '' }}</span>
                @if(!empty($officeName)) - {{ $officeName }}@endif
                @if(!empty($divisionName)) - {{ $divisionName }}@endif
            </div>
            <div class="head-title">{{ $reportTitle }} <span style="font-weight:normal;font-size:8px;margin-left:8px;">{{ $isoCode ?? '' }}</span></div>
            <div class="head-fund">{{ $fundLabel }}</div>
        </div>
        <p style="text-align:center; color:#777; padding:16px;">No documents found for these filters.</p>
    @endforelse

    @if(!empty($showPageNumber))
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $w = $pdf->get_width();
            $h = $pdf->get_height();
            $pdf->page_text($w / 2 - 20, $h - 18, "Page {PAGE_NUM}", $font, 7.5, array(0.3,0.3,0.3));
        }
    </script>
    @endif
</body>
</html>
