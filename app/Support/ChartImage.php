<?php

namespace App\Support;

/**
 * Renders simple charts as PNG data-URIs using GD. PNGs embed reliably in
 * DomPDF (unlike inline SVG), so report PDFs always show their charts in colour.
 */
class ChartImage
{
    /**
     * A pie chart. $data is [label => value]; $colors is a list of hex colours.
     * Returns a `data:image/png;base64,...` URI ready for an <img src>.
     */
    public static function pie(array $data, array $colors, int $size = 320): string
    {
        $data = array_values(array_filter($data, fn ($v) => $v > 0));
        $colors = array_values($colors);

        $img = imagecreatetruecolor($size, $size);
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $transparent);

        $total = array_sum($data);
        $cx = $size / 2;
        $cy = $size / 2;
        $d = $size - 12;

        if ($total <= 0) {
            $grey = imagecolorallocate($img, 230, 230, 235);
            imagefilledellipse($img, (int) $cx, (int) $cy, $d, $d, $grey);

            return self::toDataUri($img);
        }

        // Single 100% slice: draw a full circle (imagefilledarc can skip a 360° arc).
        if (count($data) === 1) {
            imagefilledellipse($img, (int) $cx, (int) $cy, $d, $d, self::alloc($img, $colors[0] ?? '#6366f1'));

            return self::toDataUri($img);
        }

        $start = 270.0; // start at 12 o'clock
        foreach ($data as $i => $val) {
            $sweep = $val / $total * 360;
            $end = $start + $sweep;
            imagefilledarc($img, (int) $cx, (int) $cy, $d, $d,
                (int) round($start), (int) round($end),
                self::alloc($img, $colors[$i % count($colors)]), IMG_ARC_PIE);
            $start = $end;
        }

        return self::toDataUri($img);
    }

    private static function alloc($img, string $hex)
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return imagecolorallocate($img, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
    }

    private static function toDataUri($img): string
    {
        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
