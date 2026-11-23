<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Manager;
use App\Models\Meeting;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MeetingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'manager_id' => 'required|integer|exists:managers,id',
            'starts_at' => [
                'required',
                'date',
                'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|\+00:00)$/',
            ],
        ]);

        $starts_at = CarbonImmutable::parse($request->starts_at)->utc();
        $ends_at = $starts_at->addHour();

        $local_start = $starts_at->setTimezone('Asia/Almaty');
        $local_end = $ends_at->setTimezone('Asia/Almaty');

        if ($local_start->isWeekend() || $local_start->lt($local_start->setTime(9, 0)) || $local_end->gt($local_start->setTime(18, 0))) {
            throw ValidationException::withMessages([
                'starts_at' => 'Встречи доступны с понедельника по пятницу, с 09:00 до 18:00 по времени Алматы.',
            ]);
        }

        DB::beginTransaction();
        try {
            Company::whereKey($request->company_id)->lockForUpdate()->firstOrFail();
            Manager::whereKey($request->manager_id)->lockForUpdate()->firstOrFail();

            $company_busy = Meeting::where('company_id', $request->company_id)
                ->where('status', 'scheduled')
                ->where('ends_at', '>', now('UTC'))
                ->exists();

            if ($company_busy) {
                DB::rollBack();

                return response()->json([
                    'message' => 'У организации уже есть активная встреча.',
                ], 409);
            }

            $manager_busy = Meeting::where('manager_id', $request->manager_id)
                ->where('status', 'scheduled')
                ->where('starts_at', '<', $ends_at)
                ->where('ends_at', '>', $starts_at)
                ->exists();

            if ($manager_busy) {
                DB::rollBack();

                return response()->json([
                    'message' => 'У менеджера уже есть встреча на выбранное время.',
                ], 409);
            }

            $meeting = Meeting::create([
                ...$request->only(['company_id', 'manager_id']),
                'starts_at' => $starts_at,
                'ends_at' => $ends_at,
                'original_starts_at' => $starts_at,
            ]);

            DB::commit();

            return response()->json([
                'id' => $meeting->id,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'starts_at' => [
                'sometimes',
                'required',
                'date',
                'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|\+00:00)$/',
            ],
            'status' => 'sometimes|required|in:cancelled',
        ]);

        if ($request->has('starts_at') === $request->has('status')) {
            throw ValidationException::withMessages([
                'starts_at' => 'Укажите либо новое время, либо status=cancelled.',
            ]);
        }

        $starts_at = null;
        $ends_at = null;

        if ($request->has('starts_at')) {
            $starts_at = CarbonImmutable::parse($request->starts_at)->utc();
            $ends_at = $starts_at->addHour();

            $local_start = $starts_at->setTimezone('Asia/Almaty');
            $local_end = $ends_at->setTimezone('Asia/Almaty');

            if ($local_start->isWeekend() || $local_start->lt($local_start->setTime(9, 0)) || $local_end->gt($local_start->setTime(18, 0))) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Встречи доступны с понедельника по пятницу, с 09:00 до 18:00 по времени Алматы.',
                ]);
            }
        }

        $existing_meeting = Meeting::findOrFail($id);

        DB::beginTransaction();
        try {
            Company::whereKey($existing_meeting->company_id)->lockForUpdate()->firstOrFail();
            Manager::whereKey($existing_meeting->manager_id)->lockForUpdate()->firstOrFail();

            $meeting = Meeting::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($meeting->status === 'cancelled') {
                DB::rollBack();

                return response()->json([
                    'message' => 'Встреча уже отменена.',
                ], 409);
            }

            if ($request->has('status')) {
                $meeting->update([
                    'status' => 'cancelled',
                ]);
            } else {
                $company_busy = Meeting::where('company_id', $meeting->company_id)
                    ->where('id', '!=', $meeting->id)
                    ->where('status', 'scheduled')
                    ->where('ends_at', '>', now('UTC'))
                    ->exists();

                if ($company_busy) {
                    DB::rollBack();

                    return response()->json([
                        'message' => 'У организации уже есть другая активная встреча.',
                    ], 409);
                }

                $manager_busy = Meeting::where('manager_id', $meeting->manager_id)
                    ->where('id', '!=', $meeting->id)
                    ->where('status', 'scheduled')
                    ->where('starts_at', '<', $ends_at)
                    ->where('ends_at', '>', $starts_at)
                    ->exists();

                if ($manager_busy) {
                    DB::rollBack();

                    return response()->json([
                        'message' => 'У менеджера уже есть встреча на выбранное время.',
                    ], 409);
                }

                $meeting->update([
                    'starts_at' => $starts_at,
                    'ends_at' => $ends_at,
                ]);
            }

            DB::commit();

            return response()->json([
                'id' => $meeting->id,
                'status' => $meeting->status,
                'starts_at' => $meeting->starts_at->toISOString(),
                'ends_at' => $meeting->ends_at->toISOString(),
                'original_starts_at' => $meeting->original_starts_at->toISOString(),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
