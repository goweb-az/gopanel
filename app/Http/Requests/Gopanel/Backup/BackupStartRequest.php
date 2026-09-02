<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Backup;

use App\Enums\Gopanel\BackupType;
use App\Http\Requests\Gopanel\GopanelFormRequest;
use Illuminate\Validation\Rule;

class BackupStartRequest extends GopanelFormRequest
{
    protected string $module = 'gopanel.backup';

    /** Backup çıxarmaq həmişə «əlavə» əməliyyatıdır - redaktə anlayışı yoxdur. */
    protected function ability(): string
    {
        return $this->module . '.add';
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(BackupType::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Backup növü seçilməyib.',
            'type.in'       => 'Belə bir backup növü yoxdur.',
        ];
    }

    public function backupType(): BackupType
    {
        return BackupType::from((string) $this->input('type'));
    }
}
