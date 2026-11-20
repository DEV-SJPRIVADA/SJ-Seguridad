<?php

namespace App\Models\Matrices;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatrixAreaAccess extends Model
{
    protected $table = 'matrix_area_access';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'matrix_id',
        'area_key',
        'can_register',
        'can_view_others',
        'can_edit_others',
        'granted_by_user_id',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'can_register' => 'boolean',
            'can_view_others' => 'boolean',
            'can_edit_others' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function matrix(): BelongsTo
    {
        return $this->belongsTo(Matrix::class, 'matrix_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }

    public function areaLabel(): string
    {
        return (string) config("access.areas.{$this->area_key}", $this->area_key);
    }

    /**
     * @param  Builder<MatrixAreaAccess>  $query
     * @return Builder<MatrixAreaAccess>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $inner): void {
            $inner->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}
