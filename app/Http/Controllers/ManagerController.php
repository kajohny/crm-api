<?php

namespace App\Http\Controllers;

use App\Models\Manager;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
    public function schedule(Request $request, int $id)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $manager = Manager::findOrFail($id);

        $start = CarbonImmutable::parse($request->date, 'Asia/Almaty')->startOfDay()->utc();
        $end = $start->setTimezone('Asia/Almaty')->addDay()->utc();

        $meetings = $manager->meetings()
            ->with('company')
            ->where('status', 'scheduled')
            ->where('starts_at', '>=', $start->toIso8601String())
            ->where('starts_at', '<', $end->toIso8601String())
            ->orderBy('starts_at')
            ->get();

        return response()->json([
            'manager_id' => $manager->id,
            'date' => $request->date,
            'meetings' => $meetings->map(fn($meeting) => [
                'id' => $meeting->id,
                'company_id' => $meeting->company_id,
                'company_name' => $meeting->company->name,
                'starts_at' => $meeting->starts_at->toISOString(),
                'ends_at' => $meeting->ends_at->toISOString(),
            ]),
        ]);
    }
}
