<?php

use Closure;
use Illuminate\Contracts\Routing\TerminableMiddleware;

class StartSession implements TerminableMiddleware {

    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {

        exit("HJERE");
        mail('russell@smallfri.com', gethostname().' Died With Errors! '.time(), $response);

        // Store the session data...
    }

}