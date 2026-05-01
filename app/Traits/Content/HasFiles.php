<?php

namespace App\Traits\Content;

trait HasFiles
{
    public function getAttribute($key)
    {
        if (property_exists($this, 'files') && is_array($this->files)) {
            if (substr($key, -4) === '_url') {
                $fileAttribute = substr($key, 0, -4);
                if (in_array($fileAttribute, $this->files)) {
                    return $this->getFileUrl($fileAttribute);
                }
            }

            if (property_exists($this, 'fillable') && is_array($this->fillable)) {
                if (substr($key, -5) === '_view') {
                    $field = substr($key, 0, -5);
                    if (in_array($field, $this->fillable)) {
                        return $this->getFieldView($field);
                    }
                }
            }
        }

        return parent::getAttribute($key);
    }

    public function getFileUrl(string $file): ?string
    {
        if (is_null($this->{$file})) {
            return null;
        }

        if (file_exists(public_path($this->{$file}))) {
            return url($this->{$file});
        }

        return $this->{$file};
    }

    public function getFieldView(string $field): ?string
    {
        if (is_null($this->{$field})) {
            return null;
        }

        $ext = strtolower(pathinfo($this->{$field}, PATHINFO_EXTENSION));
        $path = $this->getFileUrl($field);

        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'])) {
            return '<a href="' . $path . '" class="image-lightbox" title="' . class_basename($this) . '"><img src="' . $path . '" width="50"></a>';
        }

        return '<a href="' . $path . '" target="_blank">Fayla bax</a>';
    }
}
