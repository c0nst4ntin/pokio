<?php

declare(strict_types=1);

namespace Pokio;

use Closure;
use Pokio\Contracts\Result;
use Throwable;

/**
 * @template TReturn
 */
final class Promise
{
    private Result $result;

    /**
     * Creates a new promise instance.
     *
     * @param  Closure(): TReturn  $callback
     */
    public function __construct(private readonly Closure $callback, private readonly ?Closure $rescue = null)
    {
        //
    }

    public function run(): void
    {
        $runtime = Environment::runtime();

        $this->result = $runtime->defer($this->callback, $this->rescue);
    }

    /**
     * Resolves the promise.
     *
     * @return TReturn
     */
    public function resolve(): mixed
    {
        return $this->result->get();
    }

    /**
     * Chains a callback to be executed after the promise resolves.
     *
     * @template TNextReturn
     *
     * @param  Closure(TReturn): TNextReturn  $onFulfilled
     * @return Promise<TNextReturn>
     */
    public function then(Closure $onFulfilled): self
    {
        return async(function () use ($onFulfilled) {
            $result = await($this);

            return $onFulfilled($result);
        });
    }

    /**
     * Catches any exception thrown in the promise chain.
     *
     * @param  Closure(Throwable): mixed  $onRejected
     * @return Promise<mixed>
     */
    public function catch(Closure $onRejected): self
    {
        return async(function () use ($onRejected) {
            try {
                return await($this);
            } catch (Throwable $th) {
                return $onRejected($th);
            }
        });
    }

    /**
     * Executes a callback regardless of success or failure.
     *
     * @param  Closure(): void  $onFinally
     * @return Promise<TReturn>
     */
    public function finally(Closure $onFinally): self
    {
        return async(function () use ($onFinally) {
            try {
                $result = await($this);
                $onFinally();

                return $result;
            } catch (Throwable $e) {
                $onFinally();
                throw $e;
            }
        });
    }
}
