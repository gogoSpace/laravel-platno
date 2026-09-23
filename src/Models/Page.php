<?php

declare(strict_types=1);

namespace Platno\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Page extends Model
{
    protected $table = 'platno_pages';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['document' => 'array', 'revision' => 'integer', 'archived_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Publication, $this> */
    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }
}
