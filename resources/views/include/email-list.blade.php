<!-- EMAIL STORAGE -->
<x-adminlte-card title="{{ __('general_content.attached_mail_trans_key') }}" theme="secondary" theme-mode="outline" collapsible="collapsed" maximizable>
    @if($mailsList->isEmpty())
            <p>{{ __('general_content.no_data_trans_key') }}</p>
        @else
        <ul class="list-unstyled">
            @forelse($mailsList as $email)
            <li>
                    {{ $email->to }}</td>
                    <td>{{ $email->subject }}</td>
                    <td>{{ $email->created_at->format('d/m/Y H:i') }}</td>
                </li>
                @empty
                {{ __('general_content.no_data_trans_key') }}
            @endforelse
            </ul>
        @endif
</x-adminlte-card>