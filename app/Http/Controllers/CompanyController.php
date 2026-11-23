<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'bin' => 'required|string|regex:/^\d{12}$/|unique:companies,bin',
            'phone' => 'required|string|max:32',
        ]);

        $company = Company::create(
            $request->only(['name', 'bin', 'phone'])
        );

        return response()->json([
            'id' => $company->id,
        ], 201);
    }

    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:new,no_answer,refused,meeting_set',
        ]);

        $status = $request->query('status');

        $query = Company::query()->with('latestCall');

        if ($status === 'new') {
            $query->doesntHave('calls');
        } elseif ($status) {
            $query->whereHas('latestCall', function ($query) use ($status) {
                $query->where('result', $status);
            });
        }

        return response()->json(
            $query->orderBy('id')->paginate(10)->through(fn($company) => [
                'id' => $company->id,
                'name' => $company->name,
                'bin' => $company->bin,
                'phone' => $company->phone,
                'status' => $company->latestCall?->result?->value ?? 'new',
            ])
        );
    }
}
