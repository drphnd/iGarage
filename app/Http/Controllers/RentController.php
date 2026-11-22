<?php

namespace App\Http\Controllers;

use App\Models\EvCharge;
use App\Models\Garage;
use App\Models\Rent;
use App\Models\Ride;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RentController
{
    public function index(Request $request): View
    {
        $rents = $request->user()->rents()
            ->with(['garage', 'ride', 'evCharges', 'rentExtends'])
            ->orderByDesc('start_date')
            ->get();

        return view('renter.rents.index', compact('rents'));
    }

    public function create(Request $request): View
    {
        $period = null;
        $garages = collect();

        if ($request->filled(['ride_type', 'rent_type', 'start_date'])) {
            $data = $request->validate($this->periodRules(), $this->messages());
            $period = $this->period($data);

            $allowedTypes = Ride::ALLOWED_GARAGES[$data['ride_type']];

            $availableIds = Garage::availableBetween($period['start'], $period['end'])
                ->whereIn('type', $allowedTypes)
                ->pluck('id');

            $garages = Garage::whereIn('type', $allowedTypes)
                ->orderBy('type')
                ->orderBy('code')
                ->get()
                ->each(function (Garage $garage) use ($availableIds, $data, $period) {
                    $garage->is_available = $availableIds->contains($garage->id);
                    $garage->period_fee = Rent::feeFor($data['rent_type'], $period['days'], $garage);
                });
        }

        return view('renter.rents.create', compact('period', 'garages'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(array_merge($this->periodRules(), [
            'plate_number' => ['nullable', 'required_unless:ride_type,' . Ride::TYPE_BICYCLE, 'string', 'max:15', 'regex:/^[A-Za-z0-9 ]+$/'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'battery_capacity_kwh' => ['nullable', 'required_if:ride_type,' . Ride::TYPE_ELECTRIC_CAR, 'numeric', 'min:1', 'max:500'],
            'battery_level_kwh' => ['nullable', 'required_if:ride_type,' . Ride::TYPE_ELECTRIC_CAR, 'numeric', 'min:0', 'lte:battery_capacity_kwh'],
            'garage_id' => ['required', 'integer', 'exists:garages,id'],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]), $this->messages());

        $period = $this->period($data);
        $user = $request->user();
        $garage = Garage::findOrFail($data['garage_id']);
        $rideType = $data['ride_type'];
        $isElectricCar = $rideType === Ride::TYPE_ELECTRIC_CAR;
        $plate = $rideType === Ride::TYPE_BICYCLE ? null : Ride::normalizePlate($data['plate_number']);

        if (! in_array($garage->type, Ride::ALLOWED_GARAGES[$rideType], true)) {
            throw ValidationException::withMessages([
                'garage_id' => 'A ' . strtolower(Ride::TYPE_LABELS[$rideType]) . ' cannot park in a '
                    . strtolower(Garage::TYPE_LABELS[$garage->type]) . ' garage.',
            ]);
        }

        $stillAvailable = Garage::availableBetween($period['start'], $period['end'])->whereKey($garage->id)->exists();

        if (! $stillAvailable) {
            throw ValidationException::withMessages([
                'garage_id' => "Garage {$garage->code} was just booked for these dates. Please choose another garage.",
            ]);
        }

        if ($plate) {
            $vehicleBusy = Rent::whereHas('ride', fn ($query) => $query->where('user_id', $user->id)->where('plate_number', $plate))
                ->where('start_date', '<', $period['end'])
                ->where('end_date', '>', $period['start'])
                ->exists();

            if ($vehicleBusy) {
                throw ValidationException::withMessages([
                    'plate_number' => "Vehicle {$plate} already has a rent in this period.",
                ]);
            }
        }

        $proofPath = $request->file('payment_proof')->store('payment-proofs', 'public');

        $rent = DB::transaction(function () use ($user, $data, $period, $garage, $rideType, $isElectricCar, $plate, $proofPath) {
            $ride = $plate
                ? $user->rides()->firstOrNew(['plate_number' => $plate])
                : $user->rides()->make();

            $ride->fill([
                'type' => $rideType,
                'brand' => $data['brand'],
                'model' => $data['model'],
                'battery_capacity_kwh' => $isElectricCar ? $data['battery_capacity_kwh'] : null,
            ])->save();

            $rent = $ride->rents()->create([
                'garage_id' => $garage->id,
                'rent_type' => $data['rent_type'],
                'days' => $period['days'],
                'start_date' => $period['start'],
                'end_date' => $period['end'],
                'rent_fee' => Rent::feeFor($data['rent_type'], $period['days'], $garage),
                'payment_proof' => $proofPath,
            ]);

            if ($isElectricCar && $garage->isElectric()) {
                $kwh = EvCharge::kwhToFull((float) $data['battery_capacity_kwh'], (float) $data['battery_level_kwh']);

                if ($kwh > 0) {
                    $rent->evCharges()->create([
                        'battery_level_kwh' => $data['battery_level_kwh'],
                        'charged_kwh' => $kwh,
                        'price_per_kwh' => $garage->price_per_kwh,
                        'fee' => EvCharge::feeFor($kwh, $garage->price_per_kwh),
                        'payment_proof' => $proofPath,
                    ]);
                }
            }

            return $rent;
        });

        return redirect()->route('my-rents')->with('success',
            "Garage {$garage->code} is booked from {$rent->start_date->format('d M Y')} until {$rent->end_date->format('d M Y')}.");
    }

    private function periodRules(): array
    {
        return [
            'ride_type' => ['required', Rule::in(array_keys(Ride::TYPE_LABELS))],
            'rent_type' => ['required', Rule::in(array_keys(Rent::TYPE_LABELS))],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:' . today()->addYear()->toDateString()],
            'days' => ['nullable', 'required_if:rent_type,' . Rent::TYPE_DAILY, 'integer', 'min:1'],
        ];
    }

    private function period(array $data): array
    {
        $start = Carbon::parse($data['start_date'])->startOfDay();

        if ($data['rent_type'] === Rent::TYPE_DAILY) {
            $max = Rent::maxDailyDays($start);

            if ((int) $data['days'] > $max) {
                throw ValidationException::withMessages([
                    'days' => "A daily rent starting in {$start->format('F')} can be at most {$max} days. Choose Monthly for a full month.",
                ]);
            }
        }

        $days = Rent::daysFor($data['rent_type'], $start, isset($data['days']) ? (int) $data['days'] : null);

        return ['start' => $start, 'end' => $start->copy()->addDays($days), 'days' => $days];
    }

    private function messages(): array
    {
        return [
            'ride_type.required' => 'Please choose your vehicle type.',
            'ride_type.in' => 'Please choose a valid vehicle type.',
            'rent_type.required' => 'Please choose daily or monthly.',
            'rent_type.in' => 'Please choose daily or monthly.',
            'start_date.required' => 'Start date is required.',
            'start_date.date_format' => 'Please enter a valid start date.',
            'start_date.after_or_equal' => 'The start date cannot be in the past.',
            'start_date.before_or_equal' => 'You can book at most one year ahead.',
            'days.required_if' => 'Please enter how many days for a daily rent.',
            'days.integer' => 'Days must be a whole number.',
            'days.min' => 'Rent for at least 1 day.',
            'plate_number.required_unless' => 'Plate number is required for this vehicle.',
            'plate_number.max' => 'Plate number can be at most 15 characters.',
            'plate_number.regex' => 'Plate number may only contain letters, numbers and spaces.',
            'brand.required' => 'Brand is required.',
            'model.required' => 'Model is required.',
            'battery_capacity_kwh.required_if' => 'Battery capacity is required for an electric car.',
            'battery_capacity_kwh.numeric' => 'Battery capacity must be a number.',
            'battery_capacity_kwh.min' => 'Battery capacity must be at least 1 kWh.',
            'battery_capacity_kwh.max' => 'Battery capacity is too high.',
            'battery_level_kwh.required_if' => 'Current battery level is required for an electric car.',
            'battery_level_kwh.numeric' => 'Current battery level must be a number.',
            'battery_level_kwh.min' => 'Current battery level cannot be negative.',
            'battery_level_kwh.lte' => 'Current battery level cannot be higher than the capacity.',
            'garage_id.required' => 'Please choose a garage.',
            'garage_id.exists' => 'The chosen garage does not exist.',
            'payment_proof.required' => 'Please upload your payment proof.',
            'payment_proof.file' => 'The payment proof must be a file.',
            'payment_proof.mimes' => 'The payment proof must be a JPG, PNG or PDF file.',
            'payment_proof.max' => 'The payment proof can be at most 2 MB.',
        ];
    }
}
