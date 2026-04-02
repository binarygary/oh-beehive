<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QueenStatus;
use App\Enums\VarroaMethod;
use Database\Factories\InspectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $inspected_at
 * @property QueenStatus|null $queen_status
 * @property VarroaMethod|null $varroa_method
 * @property array<int, string>|null $disease_observations
 * @property array<int, string>|null $followup_questions
 */
#[Fillable([
    'hive_id', 'user_id', 'inspected_at', 'raw_notes',
    'queen_seen', 'queen_status',
    'eggs_present', 'larvae_present', 'capped_brood_present', 'brood_pattern_score',
    'frames_of_brood', 'frames_of_bees', 'frames_of_honey', 'honey_stores_score',
    'varroa_count', 'varroa_method',
    'temperament_score', 'disease_observations', 'overall_health_score',
    'feeding_done', 'feeding_notes', 'treatment_applied',
    'supers_added', 'supers_removed',
    'weather', 'followup_questions',
])]
class Inspection extends Model
{
    /** @use HasFactory<InspectionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'inspected_at' => 'datetime',
            'queen_seen' => 'boolean',
            'queen_status' => QueenStatus::class,
            'eggs_present' => 'boolean',
            'larvae_present' => 'boolean',
            'capped_brood_present' => 'boolean',
            'feeding_done' => 'boolean',
            'varroa_method' => VarroaMethod::class,
            'disease_observations' => 'array',
            'followup_questions' => 'array',
        ];
    }

    /** @return BelongsTo<Hive, $this> */
    public function hive(): BelongsTo
    {
        return $this->belongsTo(Hive::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
