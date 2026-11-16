<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    private function rules(): array
    {
        return [
            'address_line' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $request->validate(['make_default' => ['sometimes', 'boolean']]);
        $customer = Auth::guard('web')->user();

        DB::transaction(function () use ($customer, $validated, $request) {
            $makeDefault = !$customer->addresses()->exists()
                || $request->boolean('make_default');

            if ($makeDefault) {
                $customer->addresses()->update(['is_default' => false]);
            }

            $customer->addresses()->create($validated + [
                'is_default' => $makeDefault,
            ]);
        });

        return redirect(route('customer.profile.index') . '#addresses')
            ->with('success', 'Address saved successfully.');
    }

    public function update(Request $request, int $address): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $customer = Auth::guard('web')->user();
        $record = $customer->addresses()->findOrFail($address);
        $record->update($validated);

        return redirect(route('customer.profile.index') . '#addresses')
            ->with('success', 'Address updated successfully.');
    }

    public function setDefault(int $address): RedirectResponse
    {
        $customer = Auth::guard('web')->user();

        DB::transaction(function () use ($customer, $address) {
            $record = $customer->addresses()->findOrFail($address);
            $customer->addresses()->update(['is_default' => false]);
            $record->update(['is_default' => true]);
        });

        return redirect(route('customer.profile.index') . '#addresses')
            ->with('success', 'Default delivery address updated.');
    }

    public function destroy(int $address): RedirectResponse
    {
        $customer = Auth::guard('web')->user();

        DB::transaction(function () use ($customer, $address) {
            $record = $customer->addresses()->findOrFail($address);
            $wasDefault = (bool) $record->is_default;
            $record->delete();

            if ($wasDefault) {
                $replacement = $customer->addresses()->orderBy('address_id')->first();
                if ($replacement) {
                    $replacement->update(['is_default' => true]);
                }
            }
        });

        return redirect(route('customer.profile.index') . '#addresses')
            ->with('success', 'Address removed.');
    }
}
