<?php
// app/Http/Controllers/PrivacyPolicyController.php

namespace App\Http\Controllers;

use App\Http\Models\Enterprise;
use App\Http\Models\User;
use Illuminate\Http\Request;

class PrivacyPolicyController extends Controller
{
    public function index(Request $request)
    {
        $host = $request->getHost();

        $enterprise = Enterprise::where('url', $host)
                                ->firstOrFail();

        $user = User::where('email', $enterprise->email)->first();


        return view('Privacy.privacy', [
            'enterprise' => $enterprise,
            'user'       => $user,
        ]);
    }
}
