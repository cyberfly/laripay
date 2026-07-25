<?php

namespace App\Livewire\Forms;

use App\Enums\ProductType;
use App\Models\Product;
use App\Support\HtmlSanitizer;
use Livewire\Attributes\Validate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;
use RuntimeException;

class ProductForm extends Form
{
    public ?Product $product = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('required|numeric|min:0')]
    public string $price = '';

    #[Validate('required|string|size:3')]
    public string $currency = 'MYR';

    #[Validate('required')]
    public string $type = 'physical';

    #[Validate('nullable|url|max:255')]
    public string $digitalAccessUrl = '';

    public bool $isActive = true;

    /** @var array<int, TemporaryUploadedFile> */
    #[Validate(['newImages' => 'array|max:5', 'newImages.*' => 'image|max:2048'])]
    public array $newImages = [];

    /** @var array<int, string> */
    public array $existingImages = [];

    public function setProduct(Product $product): void
    {
        $this->product = $product;
        $this->name = $product->name;
        $this->description = (string) $product->description;
        $this->price = number_format($product->price_cents / 100, 2, '.', '');
        $this->currency = $product->currency;
        $this->type = $product->type->value;
        $this->digitalAccessUrl = (string) $product->digital_access_url;
        $this->isActive = $product->is_active;
        $this->existingImages = $product->images ?? [];
    }

    public function removeExistingImage(string $path): void
    {
        $this->existingImages = array_values(array_filter(
            $this->existingImages,
            fn (string $existing): bool => $existing !== $path,
        ));
    }

    public function store(): Product
    {
        $this->validate();

        $product = Product::create($this->attributes());

        $product->update(['images' => $this->storeNewImages()]);

        return $product;
    }

    public function update(): Product
    {
        $this->validate();

        $images = [...$this->existingImages, ...$this->storeNewImages()];

        $this->product->update([...$this->attributes(), 'images' => $images]);

        return $this->product;
    }

    /**
     * @return array<string, mixed>
     */
    protected function attributes(): array
    {
        return [
            'name' => $this->name,
            'description' => HtmlSanitizer::sanitize($this->description),
            'price_cents' => (int) round(((float) $this->price) * 100),
            'currency' => strtoupper($this->currency),
            'type' => ProductType::from($this->type),
            'digital_access_url' => $this->digitalAccessUrl ?: null,
            'is_active' => $this->isActive,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function storeNewImages(): array
    {
        return array_map(
            function ($file): string {
                $path = $file->store('products', 'public');

                throw_if($path === false, new RuntimeException('Failed to store uploaded image.'));

                return $path;
            },
            $this->newImages,
        );
    }
}
