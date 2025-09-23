<?php

namespace App\Http\Controllers;

use App\Models\AccountOfficer;
use App\Jobs\ImportAndProcessDocument;
use App\Jobs\ProcessCompletedOrders;
use App\Models\CompletedOrder;
use Illuminate\Http\Request;

class AccountOfficerController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_witel' => 'required|string|max:255',
            'filter_witel_lama' => 'required|string|max:255',
            'special_filter_column' => 'nullable|string|max:255',
            'special_filter_value' => 'nullable|string|max:255',
        ]);

        AccountOfficer::create($validated);
        return Redirect::back()->with('success', 'Agen berhasil ditambahkan.');
    }

    public function update(Request $request, AccountOfficer $officer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_witel' => 'required|string|max:255',
            'filter_witel_lama' => 'required|string|max:255',
            'special_filter_column' => 'nullable|string|max:255',
            'special_filter_value' => 'nullable|string|max:255',
        ]);

        $officer->update($validated);
        return Redirect::back()->with('success', 'Agen berhasil diperbarui.');
    }
}
