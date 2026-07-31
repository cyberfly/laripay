<?php

use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('only one of two concurrent claims on a single-use coupon succeeds', function () {
    $coupon = Coupon::factory()->create(['max_uses' => 1, 'times_used' => 0]);

    // Two checkouts that each read times_used = 0 before either claimed.
    $first = Coupon::find($coupon->id);
    $second = Coupon::find($coupon->id);

    expect($first->claimRedemption())->toBeTrue();
    expect($second->claimRedemption())->toBeFalse();

    expect($coupon->fresh()->times_used)->toBe(1);
});

test('a coupon without a usage limit can always be claimed', function () {
    $coupon = Coupon::factory()->create(['max_uses' => null, 'times_used' => 5]);

    expect($coupon->claimRedemption())->toBeTrue();
    expect($coupon->times_used)->toBe(6);
    expect($coupon->fresh()->times_used)->toBe(6);
});
