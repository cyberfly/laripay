<?php

use App\Models\User;

test('guests are redirected away from admin routes', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('login'));
});

test('non-admin users get a 403 from admin routes', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertForbidden();
});

test('admins can access admin routes', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
});
