<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin: verify new tenant registrations (approve or reject after checking the KTP).
 */
class TenantController
{
    /** Filter values accepted in ?status=... */
    private const FILTERS = [
        User::STATUS_PENDING, User::STATUS_APPROVED, User::STATUS_REJECTED, 'all',
    ];

    /**
     * List tenants, filtered by verification status (default: pending).
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

        $tenants = User::where('role', User::ROLE_TENANT)
            ->when($status !== 'all', fn ($query) => $query->where('verification_status', $status))
            ->orderBy('created_at')
            ->paginate(15)
            ->withQueryString();

        // Number of tenants per status, shown on the filter pills.
        $counts = User::where('role', User::ROLE_TENANT)
            ->selectRaw('verification_status, COUNT(*) as total')
            ->groupBy('verification_status')
            ->pluck('total', 'verification_status');

        return view('admin.tenants.index', compact('tenants', 'status', 'counts'));
    }

    /**
     * Approve a pending tenant, so they can start renting garages.
     *
     * @param  User  $tenant
     * @return RedirectResponse
     */
    public function approve(User $tenant): RedirectResponse
    {
        return $this->decide($tenant, User::STATUS_APPROVED, "{$tenant->name} disetujui dan sekarang bisa menyewa garasi.");
    }

    /**
     * Reject a pending tenant. They stay on the verification page with a "rejected" message.
     *
     * @param  User  $tenant
     * @return RedirectResponse
     */
    public function reject(User $tenant): RedirectResponse
    {
        return $this->decide($tenant, User::STATUS_REJECTED, "Pendaftaran {$tenant->name} ditolak.");
    }

    /**
     * Shared rules for approve and reject: only tenants, and only while they are still pending.
     *
     * @param  User  $tenant
     * @param  string  $newStatus  approved | rejected
     * @param  string  $message    success message for the admin
     * @return RedirectResponse
     */
    private function decide(User $tenant, string $newStatus, string $message): RedirectResponse
    {
        abort_unless($tenant->role === User::ROLE_TENANT, 404);

        if ($tenant->verification_status !== User::STATUS_PENDING) {
            return back()->with('error', "{$tenant->name} sudah diverifikasi sebelumnya.");
        }

        $tenant->verification_status = $newStatus;
        $tenant->save();

        return back()->with('success', $message);
    }
}
