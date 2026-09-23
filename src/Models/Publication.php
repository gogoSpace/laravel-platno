<?php

declare(strict_types=1);

namespace Platno\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

final class Publication extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'platno_publications';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['document' => 'array', 'source_revision' => 'integer', 'page_id' => 'integer'];
    }

    protected static function booted(): void
    {
        self::updating(fn () => throw new LogicException('Publication snapshots are immutable.'));
        self::deleting(fn () => throw new LogicException('Publication snapshots cannot be deleted.'));
    }
}
