<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\UserAutoEmailReport;
use Illuminate\Support\Facades\Auth;

class UserAutoEmailReports extends Component
{
    public array $reports = [];

    public array $reportTypes = [
        UserAutoEmailReport::REPORT_OVERDUE_ORDERS => 'general_content.overdue_orders_report_trans_key',
        UserAutoEmailReport::REPORT_TOMORROW_ORDERS => 'general_content.tomorrow_orders_report_trans_key',
        UserAutoEmailReport::REPORT_LOW_STOCK => 'general_content.low_stock_report_trans_key',
    ];

    public function mount(): void
    {
        $this->reports = [
            UserAutoEmailReport::REPORT_OVERDUE_ORDERS => [
                'enabled' => false,
                'send_time' => '08:00',
            ],
            UserAutoEmailReport::REPORT_TOMORROW_ORDERS => [
                'enabled' => false,
                'send_time' => '08:00',
            ],
            UserAutoEmailReport::REPORT_LOW_STOCK => [
                'enabled' => false,
                'send_time' => '08:00',
            ],
        ];

        $existingReports = UserAutoEmailReport::where('user_id', Auth::id())
            ->get()
            ->keyBy('report_type');

        foreach ($this->reports as $type => $data) {
            if (!$existingReports->has($type)) {
                continue;
            }

            $report = $existingReports->get($type);
            $this->reports[$type]['enabled'] = (bool) $report->enabled;
            $this->reports[$type]['send_time'] = $report->send_time;
        }
    }

    protected function rules(): array
    {
        $rules = [];

        foreach (array_keys($this->reportTypes) as $type) {
            $rules["reports.{$type}.send_time"] = ['required', 'date_format:H:i'];
            $rules["reports.{$type}.enabled"] = ['boolean'];
        }

        return $rules;
    }

    public function save(): void
    {
        $this->validate();

        foreach ($this->reports as $type => $data) {
            UserAutoEmailReport::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'report_type' => $type,
                ],
                [
                    'send_time' => $data['send_time'],
                    'enabled' => (bool) $data['enabled'],
                ]
            );
        }

        session()->flash('success', __('general_content.automatic_email_reports_saved_trans_key'));
    }

    public function render()
    {
        return view('livewire.user-auto-email-reports');
    }
}
