<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Model\Coupon;
use App\Model\UserPixel;

class CheckoutController extends Controller
{
    public function __construct()
    {
    }

    public function index(Request $request, $username, $coupon_code = null)
    {
        $user = User::where('username', $username)->firstOrFail();
        $coupon = null;
        $pixel_user = [];

        if ($coupon_code) {
            $coupon = Coupon::where('coupon_code', $coupon_code)
                ->where('creator_id', $user->id)
                ->where('status', 'active')
                ->first();
        }

        $pixels = UserPixel::where('user_id', $user->id)->get();

        foreach ($pixels as $pixel) {
            $pixel_user[$pixel->type . "-head"] = $pixel->head;
            $pixel_user[$pixel->type . "-body"] = $pixel->body;
        }

        return view('pages.checkout', compact('user', 'coupon', 'pixel_user'));
    }
}
