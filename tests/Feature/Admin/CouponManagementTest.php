<?php

use App\Enums\DiscountType;
use App\Models\Coupon;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('admin can create a fixed coupon', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.coupons.create')
        ->set('form.code', 'save10')
        ->set('form.discountType', 'fixed')
        ->set('form.discountValue', '10.00')
        ->call('save')
        ->assertHasNoErrors();

    $coupon = Coupon::first();

    expect($coupon)->not->toBeNull();
    expect($coupon->code)->toBe('SAVE10');
    expect($coupon->discount_type)->toBe(DiscountType::Fixed);
    expect($coupon->discount_value)->toBe(1000);
});

test('admin can create a percentage coupon', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.coupons.create')
        ->set('form.code', 'PERCENT10')
        ->set('form.discountType', 'percentage')
        ->set('form.discountValue', '10')
        ->call('save')
        ->assertHasNoErrors();

    $coupon = Coupon::first();

    expect($coupon->discount_type)->toBe(DiscountType::Percentage);
    expect($coupon->discount_value)->toBe(10);
});

test('a percentage coupon value over 100 is rejected', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.coupons.create')
        ->set('form.code', 'TOOBIG')
        ->set('form.discountType', 'percentage')
        ->set('form.discountValue', '150')
        ->call('save')
        ->assertHasErrors(['form.discountValue']);

    expect(Coupon::count())->toBe(0);
});

test('creating a coupon with a duplicate code fails validation', function () {
    Coupon::factory()->create(['code' => 'SAVE10']);

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.coupons.create')
        ->set('form.code', 'SAVE10')
        ->set('form.discountType', 'fixed')
        ->set('form.discountValue', '5.00')
        ->call('save')
        ->assertHasErrors(['form.code']);
});

test('admin can edit a coupon', function () {
    $coupon = Coupon::factory()->create(['code' => 'OLDCODE']);

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.coupons.edit', ['coupon' => $coupon])
        ->set('form.code', 'NEWCODE')
        ->call('save')
        ->assertHasNoErrors();

    expect($coupon->fresh()->code)->toBe('NEWCODE');
});

test('admin can delete a coupon', function () {
    $coupon = Coupon::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.coupons.index')
        ->call('delete', $coupon);

    expect(Coupon::find($coupon->id))->toBeNull();
});
