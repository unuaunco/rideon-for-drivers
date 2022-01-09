<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class RedirectIfMerchantAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public function handle($request, Closure $next)
    {

        if (Auth::guard('web')->guest()) {
            return redirect('merchants');
        }

        if (Auth::user()->status == 'Inactive') {
            Auth::logout();
            return redirect('merchants');
        }
        
        if(Auth::user()->user_type == 'Merchant'){
            return $next($request);
        }
        
        else{
            abort(403);
        }

        return $next($request);

    }
}
