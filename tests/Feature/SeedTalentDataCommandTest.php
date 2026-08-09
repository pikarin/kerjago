<?php

test('talent:seed fails cleanly when the seedgen tooling is missing', function () {
    $this->artisan('talent:seed', ['--tools-path' => base_path('tools/does-not-exist')])
        ->expectsOutputToContain('seedgen binary or data artifacts missing')
        ->assertFailed();
});

test('talent:seed is registered with the seedgen artifacts in place', function () {
    expect(is_executable(base_path('tools/seedgen/seedgen')))->toBeTrue()
        ->and(file_exists(base_path('tools/seedgen/data/typesense-snapshot.jsonl.gz')))->toBeTrue()
        ->and(file_exists(base_path('tools/seedgen/data/users.csv.gz')))->toBeTrue();
});
