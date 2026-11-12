<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HouseRule;
use Illuminate\Http\Request;

class HouseRuleController extends Controller
{
    public function index()
    {
        return response()->json(
            HouseRule::where('is_active', true)->orderBy('title')->get(),
            200
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $rule = HouseRule::create($validated);

        return response()->json([
            'message'    => 'House rule created successfully',
            'house_rule' => $rule,
        ], 201);
    }

    public function show(HouseRule $houseRule)
    {
        return response()->json($houseRule, 200);
    }

    public function update(Request $request, HouseRule $houseRule)
    {
        $validated = $request->validate([
            'title'       => ['sometimes', 'string', 'max:150'],
            'description' => ['sometimes', 'string', 'max:5000'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $houseRule->update($validated);

        return response()->json([
            'message'    => 'House rule updated successfully',
            'house_rule' => $houseRule,
        ], 200);
    }

    public function destroy(HouseRule $houseRule)
    {
        $houseRule->delete();

        return response()->json(['message' => 'House rule deleted successfully'], 200);
    }
}
