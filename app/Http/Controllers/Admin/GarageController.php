<?php

namespace App\Http\Controllers\Admin;

use App\Models\Garage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin: list all garages with their status today, and create garages in bulk.
 */
class GarageController
{
    /**
     * List garages with their derived status for today, optionally filtered by type.
     *
     * @param  Request  $request
     * @return View
     */
    public function index(Request $request): View
    {
        $type = $request->query('type', 'all');

        if (! array_key_exists($type, Garage::TYPE_LABELS)) {
            $type = 'all';
        }

        // Only rents that have not ended yet matter for today's status.
        $garages = Garage::with(['rents' => fn ($query) => $query->where('end_date', '>', today())->orderBy('start_date')])
            ->when($type !== 'all', fn ($query) => $query->where('type', $type))
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        // Status counts for the summary row.
        $statusCounts = $garages->countBy(fn (Garage $garage) => $garage->statusOn());

        // Number of garages per type for the filter pills.
        $typeCounts = Garage::selectRaw('type, COUNT(*) as total')->groupBy('type')->pluck('total', 'type');

        return view('admin.garages.index', compact('garages', 'type', 'statusCounts', 'typeCounts'));
    }

    /**
     * Show the bulk create form, with the next code of every type as a preview.
     *
     * @return View
     */
    public function create(): View
    {
        $nextCodes = [];

        foreach (array_keys(Garage::TYPE_LABELS) as $type) {
            $last = Garage::lastNumber($type);
            $nextCodes[$type] = [
                'next' => $last < Garage::MAX_PER_TYPE ? Garage::makeCode($type, $last + 1) : null,
                'remaining' => Garage::MAX_PER_TYPE - $last,
            ];
        }

        return view('admin.garages.create', compact('nextCodes'));
    }

    /**
     * Create several garages of one type at once. Codes continue from the last one (C003 → C004, C005, ...).
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Garage::TYPE_LABELS))],
            'quantity' => ['required', 'integer', 'min:1', 'max:' . Garage::MAX_PER_TYPE],
            'daily_price' => ['required', 'integer', 'min:1000', 'max:10000000'],
            'price_per_kwh' => ['nullable', 'required_if:type,' . Garage::TYPE_ELECTRIC, 'integer', 'min:1', 'max:1000000'],
        ], [
            'type.required' => 'Please choose a garage type.',
            'type.in' => 'Please choose a valid garage type.',
            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Create at least 1 garage.',
            'quantity.max' => 'You can create at most :max garages per type.',
            'daily_price.required' => 'Daily price is required.',
            'daily_price.integer' => 'Daily price must be a whole number.',
            'daily_price.min' => 'Daily price must be at least Rp 1.000.',
            'daily_price.max' => 'Daily price is too high.',
            'price_per_kwh.required_if' => 'Price per kWh is required for electric garages.',
            'price_per_kwh.integer' => 'Price per kWh must be a whole number.',
            'price_per_kwh.min' => 'Price per kWh must be at least Rp 1.',
            'price_per_kwh.max' => 'Price per kWh is too high.',
        ]);

        $type = $data['type'];
        $quantity = (int) $data['quantity'];
        $last = Garage::lastNumber($type);
        $remaining = Garage::MAX_PER_TYPE - $last;

        if ($quantity > $remaining) {
            return back()
                ->withErrors(['quantity' => "Only {$remaining} more " . Garage::TYPE_LABELS[$type] . ' garages can be created (codes stop at 999).'])
                ->withInput();
        }

        // Only electric garages have a charger, so the kWh price is ignored for other types.
        $pricePerKwh = $type === Garage::TYPE_ELECTRIC ? (int) $data['price_per_kwh'] : null;

        // All garages are created, or none (if anything fails halfway).
        DB::transaction(function () use ($type, $quantity, $last, $data, $pricePerKwh) {
            for ($number = $last + 1; $number <= $last + $quantity; $number++) {
                Garage::create([
                    'code' => Garage::makeCode($type, $number),
                    'type' => $type,
                    'daily_price' => (int) $data['daily_price'],
                    'price_per_kwh' => $pricePerKwh,
                ]);
            }
        });

        $first = Garage::makeCode($type, $last + 1);
        $lastCode = Garage::makeCode($type, $last + $quantity);
        $range = $quantity === 1 ? $first : "{$first} to {$lastCode}";

        return redirect()
            ->route('admin.garages.index', ['type' => $type])
            ->with('success', "{$quantity} " . Garage::TYPE_LABELS[$type] . " garage(s) created: {$range}.");
    }
}
