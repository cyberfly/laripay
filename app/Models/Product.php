<?php

namespace App\Models;

use App\Enums\ProductType;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $price_cents
 * @property string $currency
 * @property ProductType $type
 * @property array<int, string>|null $images
 * @property string|null $digital_file_path
 * @property string|null $digital_access_url
 * @property bool $is_active
 */
#[Fillable([
    'name', 'slug', 'description', 'price_cents', 'currency', 'type',
    'images', 'digital_file_path', 'digital_access_url', 'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'type' => ProductType::class,
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            if (! $product->slug) {
                $product->slug = static::uniqueSlugFor($product->name);
            }
        });
    }

    protected static function uniqueSlugFor(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$count}";
            $count++;
        }

        return $slug;
    }
}
