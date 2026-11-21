<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin: verify new renter registrations (approve or reject after checking the KTP).
 */
class RenterController
{
    /** Filter values accepted in ?status=... */
    private const FILTERS = [
        User::STATUS_PENDING, User::STATUS_APPROVED, User::STATUS_REJECTED, 'all',
    ];

    /**
     * List renters, filtered by verification status (default: pending).
     *
     * @param  Request  $request
     * @return View
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', User::STATUS_PENDING);

        if (! in_array($status, self::FILTERS, true)) {
            $status = User::STATUS_PENDING;
        }

        $renters = User::where('role', User::ROLE_RENTER)
            ->when($status !== 'all', fn ($query) => $query->where('verification_status', $status))
            ->orderBy('created_at')
            ->paginate(15)
            ->withQueryString();

        // Number of renters per status, shown on the filter pills.
        $counts = User::where('role', User::ROLE_RENTER)
            ->selectRaw('verification_status, COUNT(*) as total')
            ->groupBy('verification_status')
            ->pluck('total', 'verification_status');

        return view('admin.renters.index', compact('renters', 'status', 'counts'));
    }

    /**
     * Approve a pending renter, so they can start renting garages.
     *
     * @param  User  $renter
     * @return RedirectResponse
     */
    public function approve(User $renter): RedirectResponse
    {
        return $this->decide($renter, User::STATUS_APPROVED, "{$renter->name} has been approved and can now rent garages.");
    }

    /**
     * Reject a pending renter. They stay on the verification page with a "rejected" message.
     *
     * @param  User  $renter
     * @return RedirectResponse
     */
    public function reject(User $renter): RedirectResponse
    {
        return $this->decide($renter, User::STATUS_REJECTED, "{$renter->name}'s registration has been rejected.");
    }

    /**
     * Shared rules for approve and reject: only renters, and only while they are still pending.
     *
     * @param  User  $renter
     * @param  string  $newStatus  approved | rejected
     * @param  string  $message    success message for the admin
     * @return RedirectResponse
     */
    private function decide(User $renter, string $newStatus, string $message): RedirectResponse
    {
        abort_unless($renter->role === User::ROLE_RENTER, 404);

        if ($renter->verification_status !== User::STATUS_PENDING) {
            return back()->with('error', "{$renter->name} has already been verified.");
        }

        $renter->verification_status = $newStatus;
        $renter->save();

        return back()->with('success', $message);
    }
}
