<?php

namespace App\Http\Controllers;

use App\Models\Fund;
use App\Models\NatureOfTransaction;
use App\Models\ResponsibilityCenter;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function index()
    {
        return view('accounting.index', [
            'funds' => Fund::orderBy('sort_order')->orderBy('name')->get(),
            'centers' => ResponsibilityCenter::orderBy('sort_order')->orderBy('name')->get(),
            'natures' => NatureOfTransaction::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    /* ---------------- Funds ---------------- */
    public function storeFund(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20'],
            'hospital_available' => ['nullable', 'boolean'],
        ]);
        Fund::create([
            'name' => $data['name'], 'code' => $data['code'],
            'hospital_available' => $request->boolean('hospital_available'),
            'sort_order' => Fund::max('sort_order') + 1,
        ]);

        return back()->with('success', 'Fund added.');
    }

    public function updateFund(Request $request, Fund $fund)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20'],
            'hospital_available' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $fund->update([
            'name' => $data['name'], 'code' => $data['code'],
            'hospital_available' => $request->boolean('hospital_available'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Fund updated.');
    }

    public function destroyFund(Fund $fund)
    {
        $fund->delete();

        return back()->with('success', 'Fund removed.');
    }

    /* ---------------- Responsibility Centers ---------------- */
    public function storeCenter(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);
        ResponsibilityCenter::create($data + ['sort_order' => ResponsibilityCenter::max('sort_order') + 1]);

        return back()->with('success', 'Responsibility center added.');
    }

    public function updateCenter(Request $request, ResponsibilityCenter $center)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $center->update($data + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Responsibility center updated.');
    }

    public function destroyCenter(ResponsibilityCenter $center)
    {
        $center->delete();

        return back()->with('success', 'Responsibility center removed.');
    }

    /* ---------------- Nature of Transaction ---------------- */
    public function storeNature(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150']]);
        NatureOfTransaction::create($data + ['sort_order' => NatureOfTransaction::max('sort_order') + 1]);

        return back()->with('success', 'Nature of transaction added.');
    }

    public function updateNature(Request $request, NatureOfTransaction $nature)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $nature->update($data + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Nature of transaction updated.');
    }

    public function destroyNature(NatureOfTransaction $nature)
    {
        $nature->delete();

        return back()->with('success', 'Nature of transaction removed.');
    }
}
