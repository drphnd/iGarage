<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sends every signed-in user to the right start page for their role.
 */
class HomeController
{
    /**
     * Admin → garage list, approved tenant → My Rents, other tenants → verification page.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
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
