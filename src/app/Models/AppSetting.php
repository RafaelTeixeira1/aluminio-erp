<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'updated_by_user_id',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * @param array<int, string> $keys
     * @return array<string, string>
     */
    public static function valuesByKeys(array $keys): array
    {
        return self::query()
            ->whereIn('key', $keys)
            ->pluck('value', 'key')
            ->map(fn ($value) => (string) $value)
            ->all();
    }
}
