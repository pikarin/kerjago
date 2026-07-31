<?php

use App\Models\User;

test('reverb is the configured broadcaster', function () {
    expect(config('broadcasting.default'))->toBe('reverb');
});

test('a user may authorize their own private channel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-App.Models.User.'.$user->id,
            'socket_id' => '1234.5678',
        ])
        ->assertOk();
});

/**
 * Regression guard. The scaffolded channel callback compared `(int)` casts of
 * the two IDs. Every ULID casts to int 1, so that comparison authorized any
 * authenticated user on any other user's channel.
 */
test('a user may not authorize another users private channel', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    // Establishes that the bug was reachable: the broken comparison would
    // have passed here, because both distinct ULIDs collapse to the same int.
    expect((int) $user->id)->toBe((int) $other->id);

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-App.Models.User.'.$other->id,
            'socket_id' => '1234.5678',
        ])
        ->assertForbidden();
});

test('guests may not authorize a private channel', function () {
    $user = User::factory()->create();

    $this->postJson('/broadcasting/auth', [
        'channel_name' => 'private-App.Models.User.'.$user->id,
        'socket_id' => '1234.5678',
    ])->assertForbidden();
});
