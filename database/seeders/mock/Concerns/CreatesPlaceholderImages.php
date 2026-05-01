<?php

namespace Database\Seeders\mock\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait CreatesPlaceholderImages
{
    protected function placeholderImage(string $folder, string $label, int $width = 800, int $height = 520): string
    {
        $directory = public_path("site/mock/{$folder}");
        File::ensureDirectoryExists($directory);

        $fileName = Str::slug($label) . "-{$width}x{$height}.png";
        $path = "{$directory}/{$fileName}";

        if (!File::exists($path)) {
            $url = 'https://placehold.co/' . $width . 'x' . $height . '/556ee6/ffffff.png?text=' . rawurlencode($label);
            $contents = @file_get_contents($url);

            if ($contents === false) {
                $contents = $this->fallbackPlaceholderSvg($label, $width, $height);
                $fileName = Str::slug($label) . "-{$width}x{$height}.svg";
                $path = "{$directory}/{$fileName}";
            }

            File::put($path, $contents);
        }

        return "site/mock/{$folder}/{$fileName}";
    }

    private function fallbackPlaceholderSvg(string $label, int $width, int $height): string
    {
        $safeLabel = e($label);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
  <rect width="100%" height="100%" fill="#556ee6"/>
  <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#ffffff" font-family="Arial, sans-serif" font-size="32">{$safeLabel}</text>
</svg>
SVG;
    }
}
