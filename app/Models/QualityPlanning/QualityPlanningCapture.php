<?php

namespace App\Models\QualityPlanning;

use App\Models\User;
use Database\Factories\QualityPlanning\QualityPlanningCaptureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityPlanningCapture extends Model
{
    /** @use HasFactory<QualityPlanningCaptureFactory> */
    use HasFactory;

    protected $table = 'quality_planning_captures';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'complies' => false,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'indicator_version_id',
        'user_id',
        'year',
        'month',
        'input_data',
        'result_value',
        'complies',
        'analysis_text',
        'improvement_required',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'input_data' => 'array',
            'result_value' => 'decimal:4',
            'complies' => 'boolean',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(QualityPlanningIndicatorVersion::class, 'indicator_version_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
