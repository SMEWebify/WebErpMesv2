<?php

namespace App\Http\Controllers\HumanResources;

use App\Http\Controllers\Controller;
use App\Http\Requests\HumanResources\StoreLeaveBalanceRequest;
use App\Http\Requests\HumanResources\StoreLeaveTypeRequest;
use App\Http\Requests\HumanResources\UpdateLeaveTypeRequest;
use App\Models\HumanResources\LeaveBalance;
use App\Models\HumanResources\LeaveType;
use App\Models\User;
use App\Services\HumanResources\LeaveBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Leave entitlements and balances.
 *
 * Only the credit side is stored: days taken are always recomputed from the
 * absence requests by LeaveBalanceService.
 */
class LeaveController extends Controller
{
    public function __construct(private readonly LeaveBalanceService $leaveBalance)
    {
    }

    /**
     * Company-wide balance table for one reference period.
     */
    public function index(Request $request)
    {
        $reference = $this->referenceDate($request->input('period'));

        [$periodStart, $periodEnd] = $this->leaveBalance->periodFor($reference);

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'job_title', 'statu']);

        // One batch for the whole table: a per-row summary would fire three
        // queries per employee.
        $summaries = $this->leaveBalance->summaryForMany($users, $reference);

        $rows = [];

        foreach ($users as $user) {
            $rows[] = [
                'user' => $user,
                'summary' => $summaries[$user->id],
            ];
        }

        return view('admin/human-resources-leave-balances', [
            'Rows' => $rows,
            'LeaveTypes' => LeaveType::orderBy('ordre')->orderBy('label')->get(),
            'PeriodStart' => $periodStart,
            'PeriodEnd' => $periodEnd,
            'PeriodLabel' => $this->leaveBalance->periodLabel($periodStart, $periodEnd),
            'PeriodOptions' => $this->periodOptions(),
            'SelectedPeriod' => $periodStart->toDateString(),
        ]);
    }

    /**
     * Create or update the entitlement of one employee for one leave type.
     *
     * The unique key is (user, type, period start), so re-submitting the same
     * period updates the existing row instead of stacking duplicates.
     */
    public function storeBalance(StoreLeaveBalanceRequest $request)
    {
        $validated = $request->validated();

        [$periodStart, $periodEnd] = $this->leaveBalance->periodFor(Carbon::parse($validated['period_start']));

        $balance = LeaveBalance::forPeriod((int) $validated['user_id'], (int) $validated['leave_type_id'], $periodStart)->first()
            ?? new LeaveBalance([
                'user_id' => $validated['user_id'],
                'leave_type_id' => $validated['leave_type_id'],
                'period_start' => $periodStart->toDateString(),
            ]);

        $balance->fill([
            'period_end' => $periodEnd->toDateString(),
            'entitled_days' => $validated['entitled_days'] ?? 0,
            'carried_over_days' => $validated['carried_over_days'] ?? 0,
            'adjustment_days' => $validated['adjustment_days'] ?? 0,
            'comment' => $validated['comment'] ?? null,
        ])->save();

        return redirect()
            ->route('human.resources.show.user', ['id' => $validated['user_id']])
            ->with('success', __('general_content.leave_balance_saved_success_trans_key'));
    }

    /**
     * Seed the missing entitlements of a period from the default quota of each
     * leave type. Existing rows are left untouched.
     */
    public function generateBalances(Request $request)
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
        ]);

        [$periodStart, $periodEnd] = $this->leaveBalance->periodFor(Carbon::parse($validated['period_start']));

        $types = LeaveType::active()->where('counts_against_balance', true)->get();
        $users = User::query()->get(['id']);
        $created = 0;

        foreach ($users as $user) {
            foreach ($types as $type) {
                if (LeaveBalance::forPeriod((int) $user->id, (int) $type->id, $periodStart)->exists()) {
                    continue;
                }

                LeaveBalance::create([
                    'user_id' => $user->id,
                    'leave_type_id' => $type->id,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'entitled_days' => $type->default_annual_quota,
                ]);

                $created++;
            }
        }

        return redirect()
            ->route('human.resources.leave.balances', ['period' => $periodStart->toDateString()])
            ->with('success', __('general_content.leave_balance_generated_success_trans_key', ['count' => $created]));
    }

    public function storeType(StoreLeaveTypeRequest $request)
    {
        $validated = $request->validated();
        $validated['counts_against_balance'] = $request->boolean('counts_against_balance');
        $validated['active'] = $request->has('active') ? $request->boolean('active') : true;

        LeaveType::create($validated);

        return redirect()
            ->route('human.resources')
            ->with('success', __('general_content.leave_type_created_success_trans_key'));
    }

    public function updateType(UpdateLeaveTypeRequest $request, int $id)
    {
        $type = LeaveType::findOrFail($id);

        $validated = $request->validated();
        $validated['counts_against_balance'] = $request->boolean('counts_against_balance');
        $validated['active'] = $request->boolean('active');

        $type->update($validated);

        return redirect()
            ->route('human.resources')
            ->with('success', __('general_content.leave_type_updated_success_trans_key'));
    }

    /**
     * Reference date a period was asked for, defaulting to today.
     */
    private function referenceDate(mixed $period): Carbon
    {
        if (is_string($period) && $period !== '') {
            try {
                return Carbon::parse($period);
            } catch (\Throwable) {
                // Fall through to the current period.
            }
        }

        return Carbon::now();
    }

    /**
     * Current period plus the two before and the next one, for the selector.
     *
     * @return array<string, string>
     */
    private function periodOptions(): array
    {
        [$current] = $this->leaveBalance->periodFor();
        $options = [];

        foreach ([-2, -1, 0, 1] as $offset) {
            $start = $current->copy()->addYears($offset);
            [$periodStart, $periodEnd] = $this->leaveBalance->periodFor($start);
            $options[$periodStart->toDateString()] = $this->leaveBalance->periodLabel($periodStart, $periodEnd);
        }

        return $options;
    }
}
