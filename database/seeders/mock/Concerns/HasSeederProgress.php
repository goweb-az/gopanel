<?php

namespace Database\Seeders\mock\Concerns;

trait HasSeederProgress
{
    private int $_progressTotal = 0;
    private int $_progressDone  = 0;

    protected function progressStart(int $total): void
    {
        $this->_progressTotal = $total;
        $this->_progressDone  = 0;
    }

    protected function progressTick(string $label = ''): void
    {
        $this->_progressDone++;
        $pct    = $this->_progressTotal > 0
            ? (int) round($this->_progressDone / $this->_progressTotal * 100)
            : 100;
        $filled = (int) round($pct / 5);
        $bar    = str_repeat('█', $filled) . str_repeat('░', 20 - $filled);
        $suffix = $label !== '' ? "  {$label}" : '';
        $this->command?->line("  [{$bar}] {$pct}%{$suffix}");
    }

    protected function progressFinish(string $summary = ''): void
    {
        $bar  = str_repeat('█', 20);
        $text = $summary ?: "Tamamlandi! {$this->_progressDone} element.";
        $this->command?->info("  [{$bar}] 100% — {$text}");
    }
}
