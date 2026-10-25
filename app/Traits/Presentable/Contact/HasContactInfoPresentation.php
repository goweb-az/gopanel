<?php

namespace App\Traits\Presentable\Contact;

/**
 * `ContactInfo` modelinin göstəriş accessor-ları.
 *
 * Niyə: admin xəritə sahəsinə adi Google Maps linkini yapışdırır, sayt isə
 * `<iframe>` üçün embed ünvanı istəyir - çevirmə bir yerdə olsun deyə.
 * Ad DƏYİŞMƏYİB (`map_embed_url` əlaqə səhifəsində işlədilir).
 */
trait HasContactInfoPresentation
{
    public function getMapEmbedUrlAttribute(): ?string
    {
        if (empty($this->map)) {
            return null;
        }

        // artıq embed linkdirsə
        if (str_contains($this->map, '/maps/embed')) {
            return $this->map;
        }

        // koordinatlı Google Maps linki
        if (preg_match('/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $this->map, $matches)) {
            $lat = $matches[1];
            $lng = $matches[2];

            return "https://www.google.com/maps?q={$lat},{$lng}&hl=az&z=16&output=embed";
        }
        return null;
    }
}
