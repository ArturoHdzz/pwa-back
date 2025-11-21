<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    function index(Request $request)
    {
        $user = $request->user();
        $organizations = $user->organizations()->get();
        return response()->json($organizations);
    }
}
