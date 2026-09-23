<?php

declare(strict_types=1);

namespace Platno\Models;

use Illuminate\Database\Eloquent\Model;

final class Asset extends Model
{
    protected $table = 'platno_assets';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function isImage(): bool
    {
        return in_array($this->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true);
    }
}
