<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'has_ar_support',
        'requires_expiry_tracking',
        'requires_prescription',
        'stock_unit',
        'has_frame_size',
        'has_color',
        'has_material',
        'has_lens_type',
        'has_power_field',
        'has_duration',
        // is_system is intentionally excluded — set only via seeders/migrations
    ];

    protected function casts(): array
    {
        return [
            'has_ar_support'           => 'boolean',
            'requires_expiry_tracking' => 'boolean',
            'requires_prescription'    => 'boolean',
            'stock_unit'               => 'string',
            'is_system'                => 'boolean',
            'has_frame_size'           => 'boolean',
            'has_color'                => 'boolean',
            'has_material'             => 'boolean',
            'has_lens_type'            => 'boolean',
            'has_power_field'          => 'boolean',
            'has_duration'             => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function scopeSystem(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_system', false);
    }
}
