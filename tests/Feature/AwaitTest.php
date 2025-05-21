<?php

declare(strict_types=1);

test('async with a single promise', function (): void {
    $promise = async(fn (): int => 1 + 2);

    $result = await($promise);

    expect($result)->toBe(3);
})->with('runtimes');

test('async with a multiple promises', function (): void {
    $promiseA = async(fn (): int => 1 + 2);

    $promiseB = async(fn (): int => 3 + 4);

    [$resultA, $resultB] = await([$promiseA, $promiseB]);

    expect($resultA)->toBe(3)
        ->and($resultB)->toBe(7);
})->with('runtimes');

test('async with a then', function (): void {
    $promise = async(fn (): int => 1 + 2)
        ->then(fn ($result): int => $result + 2);

    $result = await($promise);

    expect($result)->toBe(5);
})->with('runtimes');

test('async with multiple thens', function (): void {
    $promise = async(fn (): int => 1 + 2)
        ->then(fn ($result): int => $result + 2)
        ->then(fn ($result): int => $result * 2);

    $result = await($promise);

    expect($result)->toBe(10);
})->with('runtimes');

test('catch handles thrown exceptions', function () {
    $promise = async(function () {
        throw new RuntimeException('Test failure');
    })->catch(function (Throwable $e) {
        return 'Recovered: ' . $e->getMessage();
    });

    expect(await($promise))->toBe('Recovered: Test failure');
});

test('catch is not called if no exception occurs', function () {
    $promise = async(fn() => 'ok')
        ->catch(function () {
            return 'should not be called';
        });

    expect(await($promise))->toBe('ok');
});

test('finally is called after successful promise', function () {
    $called = false;

    $promise = async(fn() => 'done')
        ->finally(function () use (&$called) {
            $called = true;
        });

    await($promise);

    expect($called)->toBeTrue();
});

test('finally is called after failed promise', function () {
    $called = false;

    $promise = async(function () {
        throw new Exception('fail');
    })->catch(fn() => null)
      ->finally(function () use (&$called) {
          $called = true;
      });

    await($promise);

    expect($called)->toBeTrue();
});

test('catch allows rethrowing the exception', function () {
    $promise = async(function () {
        throw new InvalidArgumentException('Invalid');
    })->catch(function (Throwable $e) {
        throw new RuntimeException('Wrapped: ' . $e->getMessage(), 0, $e);
    });

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Wrapped: Invalid');

    await($promise);
});