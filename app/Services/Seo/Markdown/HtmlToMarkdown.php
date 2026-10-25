<?php

declare(strict_types=1);

namespace App\Services\Seo\Markdown;

use DOMDocument;
use DOMNode;
use DOMText;

/**
 * Redaktor HTML-ini Markdown-a çevirir.
 *
 * Xarici paket qoşulmur - çevrilən HTML **bizim öz məzmunumuzdur** (bloq,
 * yeniliklər, SEO səhifələri) və orada yalnız məhdud teq dəsti işlədilir:
 * `p, h1-h6, ul, ol, li, strong, em, a, br, blockquote, pre, code, img, hr,
 * table/thead/tbody/tr/th/td`. Tanınmayan teq **atılmır** - içindəki mətn
 * saxlanılır, yəni məzmun heç vaxt itmir.
 *
 * Nəticə AI agentləri üçündür: sadə, təmiz, GitHub-uyğun Markdown.
 */
final class HtmlToMarkdown
{
    public function convert(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument();

        // Redaktor HTML-i tam sənəd deyil; `<?xml encoding>` olmadan DOM onu
        // ISO-8859-1 kimi oxuyur və Azərbaycan hərfləri pozulur.
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $blocks = [];

        foreach ($document->childNodes as $node) {
            $block = $this->block($node);

            if ($block !== '') {
                $blocks[] = $block;
            }
        }

        return implode("\n\n", $blocks);
    }

    /** Blok səviyyəsində element - nəticə öz-özlüyündə tam abzasdır. */
    private function block(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            return $this->text($node->textContent);
        }

        $name = strtolower($node->nodeName);

        return match (true) {
            in_array($name, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true)
                => str_repeat('#', (int) substr($name, 1)) . ' ' . $this->inline($node),
            $name === 'p'          => $this->inline($node),
            $name === 'ul'         => $this->list($node, false),
            $name === 'ol'         => $this->list($node, true),
            $name === 'blockquote' => $this->quote($node),
            $name === 'pre'        => "```\n" . trim($node->textContent) . "\n```",
            $name === 'hr'         => '---',
            $name === 'table'      => $this->table($node),
            $name === 'br'         => '',
            // div/section/article və tanınmayan konteynerlər - içəri gir
            default                => $this->children($node),
        };
    }

    /** Konteyner elementin uşaqlarını blok kimi yığır. */
    private function children(DOMNode $node): string
    {
        $blocks = [];

        foreach ($node->childNodes as $child) {
            $block = $this->block($child);

            if ($block !== '') {
                $blocks[] = $block;
            }
        }

        return implode("\n\n", $blocks);
    }

    private function list(DOMNode $node, bool $ordered): string
    {
        $lines = [];
        $index = 1;

        foreach ($node->childNodes as $child) {
            if (strtolower($child->nodeName) !== 'li') {
                continue;
            }

            $marker = $ordered ? ($index++ . '.') : '-';
            $lines[] = $marker . ' ' . $this->inline($child);
        }

        return implode("\n", $lines);
    }

    private function quote(DOMNode $node): string
    {
        $inner = $this->children($node);

        if ($inner === '') {
            $inner = $this->inline($node);
        }

        return implode("\n", array_map(
            static fn (string $line): string => rtrim('> ' . $line),
            explode("\n", $inner)
        ));
    }

    /**
     * `thead` varsa başlıq ondan, yoxsa ilk sətirdən götürülür - Markdown
     * cədvəli başlıq sətri olmadan qurulmur.
     */
    private function table(DOMNode $node): string
    {
        $rows = [];

        foreach ($this->descendants($node, 'tr') as $tr) {
            $cells = [];

            foreach ($tr->childNodes as $cell) {
                if (in_array(strtolower($cell->nodeName), ['td', 'th'], true)) {
                    $cells[] = str_replace('|', '\\|', $this->inline($cell));
                }
            }

            if ($cells !== []) {
                $rows[] = $cells;
            }
        }

        if ($rows === []) {
            return '';
        }

        $header  = array_shift($rows);
        $lines   = ['| ' . implode(' | ', $header) . ' |'];
        $lines[] = '| ' . implode(' | ', array_fill(0, count($header), '---')) . ' |';

        foreach ($rows as $row) {
            // Sətir başlıqdan qısadırsa boş xanalarla tamamlanır
            $row     = array_pad(array_slice($row, 0, count($header)), count($header), '');
            $lines[] = '| ' . implode(' | ', $row) . ' |';
        }

        return implode("\n", $lines);
    }

    /** Sətir daxili formatlaşdırma. */
    private function inline(DOMNode $node): string
    {
        $out = '';

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMText) {
                $out .= $this->text($child->textContent);

                continue;
            }

            $name  = strtolower($child->nodeName);
            $inner = $this->inline($child);

            $out .= match ($name) {
                'strong', 'b' => $inner === '' ? '' : '**' . $inner . '**',
                'em', 'i'     => $inner === '' ? '' : '*' . $inner . '*',
                'code'        => $inner === '' ? '' : '`' . $inner . '`',
                'br'          => "\n",
                'a'           => $this->link($child, $inner),
                'img'         => $this->image($child),
                default       => $inner,
            };
        }

        return trim(preg_replace('/[ \t]+/', ' ', $out) ?? $out);
    }

    private function link(DOMNode $node, string $text): string
    {
        $href = $node->attributes?->getNamedItem('href')?->nodeValue ?? '';

        if ($href === '' || $text === '') {
            return $text;
        }

        return '[' . $text . '](' . $this->absolute($href) . ')';
    }

    private function image(DOMNode $node): string
    {
        $src = $node->attributes?->getNamedItem('src')?->nodeValue ?? '';
        $alt = $node->attributes?->getNamedItem('alt')?->nodeValue ?? '';

        return $src === '' ? '' : '![' . $alt . '](' . $this->absolute($src) . ')';
    }

    /**
     * Nisbi ünvanı mütləq edir - Markdown sənədi saytdan kənarda oxunur,
     * `/az/ustunlukler` orada heç bir yerə aparmır.
     *
     * `mailto:`, `tel:`, tam URL və səhifədaxili `#fraqment` **olduğu kimi**
     * qalır; sorğu parametri əlavə edilmir.
     */
    private function absolute(string $url): string
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '#')) {
            return $url;
        }

        // Sxemi olan hər şey (http, https, mailto, tel, data, ftp…) toxunulmur
        if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url) || str_starts_with($url, '//')) {
            return $url;
        }

        $base = rtrim((string) config('app.url'), '/');

        return $base . '/' . ltrim($url, '/');
    }

    /** Boşluqlar Markdown-da mənalı olduğu üçün sıxılır. */
    private function text(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value) === ''
            ? ''
            : preg_replace('/\s+/u', ' ', $value);
    }

    /**
     * @return array<int, DOMNode>
     */
    private function descendants(DOMNode $node, string $tag): array
    {
        $found = [];

        foreach ($node->childNodes as $child) {
            if (strtolower($child->nodeName) === $tag) {
                $found[] = $child;

                continue;
            }

            $found = array_merge($found, $this->descendants($child, $tag));
        }

        return $found;
    }
}
