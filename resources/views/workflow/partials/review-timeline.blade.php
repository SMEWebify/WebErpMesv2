{{-- Historique de la revue de contrat et des demandes de changement (activitylog), onglet « Revue de contrat » de la fiche commande. --}}
        <x-adminlte-card title="{{ __('contract_review.history') }}" theme="info" theme-mode="outline" icon="fas fa-history" class="mb-4" collapsible>
          @php
            $reviewersById = $Reviewers->keyBy('id');
            $fieldLabels = [
              'reviewed_by' => __('general_content.reviewed_by_trans_key'),
              'reviewed_at' => __('general_content.review_date_trans_key'),
              'review_decision' => __('general_content.decision_trans_key'),
              'review_gaps' => __('contract_review.gaps'),
              'review_checklist' => __('contract_review.checklist_title'),
              'change_requested_by' => __('general_content.change_requested_by_trans_key'),
              'change_reason' => __('general_content.change_reason_trans_key'),
              'change_approved_at' => __('general_content.change_approved_at_trans_key'),
            ];
            $formatReviewValue = function ($field, $value) use ($reviewersById) {
                if (is_null($value) || $value === '' || $value === []) {
                    return __('general_content.undefined_trans_key');
                }

                if ($field === 'review_checklist') {
                    $checklist = is_array($value) ? $value : (json_decode((string) $value, true) ?: []);

                    return collect($checklist)
                        ->map(fn ($checked, $key) => (empty($checked) ? '✗ ' : '✓ ') . \App\Services\Workflow\ContractReview::itemLabel((string) $key))
                        ->implode(' · ');
                }

                if (in_array($field, ['reviewed_by', 'change_requested_by'], true)) {
                    return optional($reviewersById->get((int) $value))->name ?? __('general_content.undefined_trans_key');
                }

                if (in_array($field, ['reviewed_at', 'change_approved_at'], true)) {
                    try {
                        return \Carbon\Carbon::parse($value)->format('d/m/Y H:i');
                    } catch (\Exception $e) {
                        return $value;
                    }
                }

                if ($field === 'review_decision') {
                    return match ($value) {
                        'approved' => __('general_content.approved_trans_key'),
                        'rejected' => __('general_content.rejected_trans_key'),
                        'pending' => __('general_content.pending_trans_key'),
                        default => $value,
                    };
                }

                return is_scalar($value) ? $value : json_encode($value);
            };
          @endphp
          @if($ReviewTimeline->isEmpty())
            <p class="mb-0 text-body-secondary">{{ __('general_content.no_data_trans_key') }}</p>
          @else
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.created_trans_key') }}</th>
                    <th>{{ __('general_content.user_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{ __('general_content.changes_trans_key') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($ReviewTimeline as $entry)
                    <tr>
                      <td>{{ optional($entry['created_at'])->format('d/m/Y H:i') }}</td>
                      <td>{{ $entry['causer'] ?? __('general_content.undefined_trans_key') }}</td>
                      <td>{{ $entry['description'] }}</td>
                      <td>
                        <table class="table table-sm mb-0">
                          <thead>
                            <tr>
                              <th>{{ __('general_content.label_trans_key') }}</th>
                              <th>{{ __('general_content.previous_trans_key') }}</th>
                              <th>{{ __('general_content.new_trans_key') }}</th>
                            </tr>
                          </thead>
                          <tbody>
                            @foreach($entry['changes'] as $change)
                              <tr>
                                <td>{{ $fieldLabels[$change['field']] ?? \Illuminate\Support\Str::headline($change['field']) }}</td>
                                <td style="white-space: pre-line">{{ $formatReviewValue($change['field'], $change['old']) }}</td>
                                <td style="white-space: pre-line">{{ $formatReviewValue($change['field'], $change['new']) }}</td>
                              </tr>
                            @endforeach
                          </tbody>
                        </table>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </x-adminlte-card>
