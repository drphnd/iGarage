<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.garages.index');
        }

        if ($user->isApproved()) {
            return redirect()->route('my-rents');
        }

        return redirect()->route('account.verification');
    }
}
