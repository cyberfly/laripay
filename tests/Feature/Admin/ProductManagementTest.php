<?php

use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('admin can create a product with a unique slug', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.products.create')
        ->set('form.name', 'Cool Gadget')
        ->set('form.price', '99.90')
        ->set('form.currency', 'MYR')
        ->set('form.type', 'physical')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::first();

    expect($product)->not->toBeNull();
    expect($product->name)->toBe('Cool Gadget');
    expect($product->slug)->toBe('cool-gadget');
    expect($product->price_cents)->toBe(9990);
});

test('creating a product with a duplicate name generates a unique slug', function () {
    Product::factory()->create(['name' => 'Cool Gadget', 'slug' => 'cool-gadget']);

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.products.create')
        ->set('form.name', 'Cool Gadget')
        ->set('form.price', '10.00')
        ->set('form.currency', 'MYR')
        ->set('form.type', 'physical')
        ->call('save');

    expect(Product::where('name', 'Cool Gadget')->pluck('slug'))
        ->toContain('cool-gadget-1');
});

test('admin can edit a product', function () {
    $product = Product::factory()->create(['name' => 'Old Name']);

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.products.edit', ['product' => $product])
        ->set('form.name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($product->fresh()->name)->toBe('New Name');
});

test('product create form renders the rich text description editor', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.products.create')
        ->assertSeeHtml('<trix-editor');
});

test('product description is sanitized of disallowed tags and attributes on save', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.products.create')
        ->set('form.name', 'Cool Gadget')
        ->set('form.description', '<p>Great <strong>value</strong>.</p><script>alert(1)</script><a href="javascript:alert(1)" onclick="alert(1)">click</a>')
        ->set('form.price', '99.90')
        ->set('form.currency', 'MYR')
        ->set('form.type', 'physical')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::first();

    expect($product->description)->toContain('<p>Great <strong>value</strong>.</p>');
    expect($product->description)->not->toContain('<script>');
    expect($product->description)->not->toContain('onclick');
    expect($product->description)->not->toContain('javascript:');
});

test('admin can delete a product', function () {
    $product = Product::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.products.index')
        ->call('delete', $product);

    expect(Product::find($product->id))->toBeNull();
});
