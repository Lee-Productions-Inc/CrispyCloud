<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        $customer = Auth::guard('web')->user();
        $customer->load(['addresses' => function ($query) {
            $query->orderByDesc('is_default')->orderBy('address_id');
        }]);

        return view('customer.profile.index', compact('customer'));
    }

    public function update(Request $request): RedirectResponse
    {
        $customer = Auth::guard('web')->user();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($customer->user_id, 'user_id'),
            ],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ]);

        $customer->update($validated);

        return redirect()->route('customer.profile.index')
            ->with('success', 'Your profile was updated successfully.');
    }
}
