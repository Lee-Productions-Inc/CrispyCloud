<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutEntryController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (empty($request->session()->get('crispycloud_cart', []))) {
            return redirect()->route('customer.cart.index');
        }

        if (Auth::guard('web')->check()) {
            return redirect()->route('customer.checkout.index');
        }

        // Fixed internal destination: never accept an arbitrary return URL.
        $request->session()->put('crispycloud_after_auth', 'checkout');

        return redirect()->route('register');
    }
}
