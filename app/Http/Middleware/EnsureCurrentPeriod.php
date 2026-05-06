<?php

namespace App\Http\Middleware;

use App\Services\PeriodService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentPeriod
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function __construct(private readonly PeriodService $periodService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->periodService->ensureCurrentPeriodExists();

        return $next($request);
    }
}
