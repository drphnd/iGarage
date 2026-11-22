<?php

namespace App\Http\Controllers\Admin;

use App\Models\Garage;
use App\Models\Rent;
use App\Models\RentExtend;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RentExtendController
{
    public function index(): View
    {
        $rents = Rent::with(['garage', 'ride.user'])
            ->where('start_date', '<=', today())
            ->where('end_date', '>', today())
            ->orderBy('end_date')
            ->get();

        $recentExtends = RentExtend::with(['rent.garage', 'rent.ride.user'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.extends.index', compact('rents', 'recentExtends'));
    }

    public function create(Rent $rent): View|RedirectResponse
    {
        if (! $rent->isActiveToday()) {
            return redirect()->route('admin.extends.index')->with('error', 'Only rents that are active today can be extended.');
        }

        $rent->load(['garage', 'ride.user', 'rentExtends']);
        $maxEndDate = $rent->nextBookingStart();

        return view('admin.extends.create', compact('rent', 'maxEndDate'));
    }

    public function store(Request $request, Rent $rent): RedirectResponse
    {
        if (! $rent->isActiveToday()) {
            return redirect()->route('admin.extends.index')->with('error', 'Only rents that are active today can be extended.');
        }

        $oldEnd = $rent->end_date->copy();

        $data = $request->validate([
            'new_end_date' => ['required', 'date_format:Y-m-d', 'after:' . $oldEnd->toDateString(),
                'before_or_equal:' . $oldEnd->copy()->addYear()->toDateString()],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], [
            'new_end_date.required' => 'New end date is required.',
            'new_end_date.date_format' => 'Please enter a valid date.',
            'new_end_date.after' => "The new end date must be after the current end date ({$oldEnd->format('d M Y')}).",
            'new_end_date.before_or_equal' => 'A rent can be extended by at most one year at a time.',
            'payment_proof.required' => 'Please upload the payment proof.',
            'payment_proof.file' => 'The payment proof must be a file.',
            'payment_proof.mimes' => 'The payment proof must be a JPG, PNG or PDF file.',
            'payment_proof.max' => 'The payment proof can be at most 2 MB.',
        ]);

        $newEnd = Carbon::parse($data['new_end_date'])->startOfDay();

        $garageFree = Garage::availableBetween($oldEnd, $newEnd)->whereKey($rent->garage_id)->exists();

        if (! $garageFree) {
            $next = $rent->nextBookingStart();

            throw ValidationException::withMessages([
                'new_end_date' => "Garage {$rent->garage->code} is booked by another renter from {$next->format('d M Y')}, "
                    . "so this rent can be extended until {$next->format('d M Y')} at most.",
            ]);
        }

        $vehicleBusy = Rent::where('ride_id', $rent->ride_id)
            ->whereKeyNot($rent->id)
            ->where('start_date', '<', $newEnd)
            ->where('end_date', '>', $oldEnd)
            ->exists();

        if ($vehicleBusy) {
            throw ValidationException::withMessages([
                'new_end_date' => 'This vehicle already has another rent in those extra days.',
            ]);
        }

        $additionalDays = (int) $oldEnd->diffInDays($newEnd);
        $proofPath = $request->file('payment_proof')->store('payment-proofs', 'public');

        DB::transaction(function () use ($rent, $oldEnd, $newEnd, $additionalDays, $proofPath) {
            $rent->rentExtends()->create([
                'old_end_date' => $oldEnd,
                'new_end_date' => $newEnd,
                'additional_days' => $additionalDays,
                'amount_due' => $additionalDays * $rent->garage->daily_price,
                'payment_proof' => $proofPath,
            ]);

            $rent->end_date = $newEnd;
            $rent->save();
        });

        return redirect()->route('admin.extends.index')->with('success',
            "Garage {$rent->garage->code} is extended by {$additionalDays} day(s), until {$newEnd->format('d M Y')}.");
    }
}
