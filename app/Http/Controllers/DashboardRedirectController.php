<?php

namespace App\Http\Controllers;

use App\Support\RoleLandingUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->to(RoleLandingUrl::home($request->user()));
    }
}
