<?php

namespace App\Http\Controllers;

use App\Models\EvCharge;
use App\Models\Rent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvChargeController
{
    public function create(Request $request, Rent $rent): View|RedirectResponse
    {
        $this->ensureOwnRent($request, $rent);

        if (! $rent->canRequestCharge()) {
            return redirect()->route('my-rents')->with('error', $this->notAllowedMessage($rent));
        }

        $rent->load('evCharges');

        return view('renter.charges.create', compact('rent'));
    }

    public function store(Request $request, Rent $rent): RedirectResponse
    {
        $this->ensureOwnRent($request, $rent);

        if (! $rent->canRequestCharge()) {
            return redirect()->route('my-rents')->with('error', $this->notAllowedMessage($rent));
        }

        $capacity = $rent->ride->battery_capacity_kwh;

        $data = $request->validate([
            'battery_level_kwh' => ['required', 'numeric', 'min:0', 'lt:' . $capacity],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], [
            'battery_level_kwh.required' => 'Current battery level is required.',
            'battery_level_kwh.numeric' => 'Current battery level must be a number.',
            'battery_level_kwh.min' => 'Current battery level cannot be negative.',
            'battery_level_kwh.lt' => "The battery must be below its capacity ({$capacity} kWh) to be charged.",
            'payment_proof.required' => 'Please upload your payment proof.',
            'payment_proof.file' => 'The payment proof must be a file.',
            'payment_proof.mimes' => 'The payment proof must be a JPG, PNG or PDF file.',
            'payment_proof.max' => 'The payment proof can be at most 2 MB.',
        ]);

        $kwh = EvCharge::kwhToFull($capacity, (float) $data['battery_level_kwh']);
        $pricePerKwh = $rent->garage->price_per_kwh;

        $rent->evCharges()->create([
            'battery_level_kwh' => $data['battery_level_kwh'],
            'charged_kwh' => $kwh,
            'price_per_kwh' => $pricePerKwh,
            'fee' => EvCharge::feeFor($kwh, $pricePerKwh),
            'payment_proof' => $request->file('payment_proof')->store('payment-proofs', 'public'),
        ]);

        return redirect()->route('my-rents')->with('success',
            "Charging {$kwh} kWh in garage {$rent->garage->code} has been recorded.");
    }

    private function ensureOwnRent(Request $request, Rent $rent): void
    {
        abort_unless((int) $rent->ride->user_id === (int) $request->user()->id, 404);
    }

    private function notAllowedMessage(Rent $rent): string
    {
        return match (true) {
            ! $rent->ride->isElectric() => 'Charging is only for electric cars.',
            ! $rent->garage->isElectric() => "Garage {$rent->garage->code} has no charger. Charging is only available in electric garages.",
            default => 'Charging can only be requested while the rent is active.',
        };
    }
}
