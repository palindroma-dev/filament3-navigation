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

    protected $attributes = [
        'items' => '[]',
    ];

    /**
     * Get the items attribute, ensuring it's always an array
     */
    public function getItemsAttribute($value)
    {
        if ($value === null || $value === '') {
            return [];
        }
        
        $items = is_string($value) ? json_decode($value, true) : $value;
        
        if (!is_array($items)) {
            return [];
        }
        
        // Ensure all items have children as arrays
        return $this->ensureChildrenAreArrays($items);
    }

    /**
     * Recursively ensure all children are arrays
     */
    protected function ensureChildrenAreArrays(array $items): array
    {
        foreach ($items as $uuid => &$item) {
            if (!isset($item['children']) || $item['children'] === null || !is_array($item['children'])) {
                $item['children'] = [];
            } else {
                $item['children'] = $this->ensureChildrenAreArrays($item['children']);
            }
        }
        
        return $items;
    }

    public array $translatable = [
      'name',
    ];

    public static function fromHandle(string $handle): ?static
    {
        return static::query()->firstWhere('handle', $handle);
    }
}
