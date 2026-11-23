<?php

namespace App\Http\Controllers;

use App\Enums\CallResult;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CallController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'result' => ['required', Rule::enum(CallResult::class)],
            'meeting_id' => [
                'nullable',
                'integer',
                'required_if:result,meeting_set',
                'prohibited_unless:result,meeting_set',
                Rule::exists('meetings', 'id')->where('company_id', $request->input('company_id')),
            ],
        ]);

        $call = Call::create(
            $request->only(['company_id', 'meeting_id', 'result'])
        );

        return response()->json([
            'id' => $call->id,
        ], 201);
    }
}
