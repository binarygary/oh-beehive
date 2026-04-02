<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HiveStatus;
use Database\Factories\HiveFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'location', 'acquired_at', 'status', 'notes'])]
class Hive extends Model
{
    /** @use HasFactory<HiveFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'acquired_at' => 'date',
            'status' => HiveStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Inspection, $this> */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }
}
