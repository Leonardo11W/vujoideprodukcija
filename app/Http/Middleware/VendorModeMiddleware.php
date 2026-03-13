<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User; // Make sure to import your Vendor model

class VendorModeMiddleware
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

        if(config('frontend.vendor_mode') === 'single' ) {

             session(['current_vendor_id' => null]);

             return $next($request);

        }else{

        $segments = $request->segments();
        $vendorSlug = !empty($segments) ? $segments[0] : null;
        $vendor=null;

        if( $vendorSlug ){
            $vendor = User::where('slug', $vendorSlug)->first();
        }
        if ($vendorSlug && $vendor) {
      
            app()->instance('active_vendor', $vendor);
            session(['current_vendor_id' => $vendor->id]);
            
            $request->server->set('REQUEST_URI', '/' . implode('/', array_slice($segments, 1)) ?: '/');
        } else {

            session(['current_vendor_id' => null]);
            app()->instance('active_vendor', null);
            
         }

        return $next($request);

        }


    }
}
