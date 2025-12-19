<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Model\Coupon;

class CheckoutController extends Controller
{
    public function __construct()
    {
    }

    public function index(Request $request, $username, $coupon_code = null)
    {
        $user = User::where('username', $username)->firstOrFail();
        $coupon = null;
        
        if ($coupon_code) {
            $coupon = Coupon::where('coupon_code', $coupon_code)
                ->where('creator_id', $user->id)
                ->where('status', 'active')
                ->first();
        }
        return view('pages.checkout', compact('user', 'coupon'));
    }
}