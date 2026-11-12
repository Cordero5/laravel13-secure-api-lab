<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HouseRule;
use Illuminate\Http\Request;

class HouseRuleController extends Controller
{
    public function index()
    {
        $rules = HouseRule::orderByDesc('is_active')->orderBy('title')->get();

        return view('admin.rules.index', compact('rules'));
    }

    public function store(Request $request)
    {
        HouseRule::create($this->validated($request));

        return redirect()
            ->route('admin.rules.index')
            ->with('success', 'House rule added successfully.');
    }

    public function update(Request $request, HouseRule $houseRule)
    {
        $houseRule->update($this->validated($request));

        return redirect()
            ->route('admin.rules.index')
            ->with('success', 'House rule updated successfully.');
    }

    public function destroy(HouseRule $houseRule)
    {
        $houseRule->delete();

        return redirect()
            ->route('admin.rules.index')
            ->with('success', 'House rule deleted successfully.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        // An unchecked checkbox is absent from the request, so resolve it
        // explicitly instead of relying on the validated payload.
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
