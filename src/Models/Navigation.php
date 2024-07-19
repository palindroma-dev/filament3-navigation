<?php

namespace RyanChandler\FilamentNavigation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * @property string $handle
 * @property array $items
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Navigation extends Model
{
    use HasFactory, HasTranslations;

    protected $guarded = [];

    protected $casts = [
        'items' => 'json',
    ];

    public array $translatable = [
      'name',
    ];

    public static function fromHandle(string $handle): ?static
    {
        return static::query()->firstWhere('handle', $handle);
    }
}
