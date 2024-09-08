<div class="table-responsive p-0">
    <table class="table table-hover">
        <tr>
            <td style="width:50%">{{ __('general_content.sub_total_trans_key') }} :</td>
            <td>{{ number_format( $subPrice , 2)}} {{ $Factory->curency }} </td>
        </tr>
        @forelse($vatPrice as $key => $value)
        <tr>
            <td>{{ __('general_content.tax_trans_key') }} {{ $vatPrice[$key][0] }} %</td>
            <td colspan="4">{{ number_format( $vatPrice[$key][1] , 2)}} {{ $Factory->curency }}</td>
        </tr>
        @empty
        <tr>
            <td>{{ __('general_content.no_tax_trans_key') }}</td>
            <td> </td>
        </tr>
        @endforelse
        <tr>
            <td>{{ __('general_content.total_trans_key') }} :</td>
            <td>{{ number_format($totalPrices, 2) }} {{ $Factory->curency }}</td>
        </tr>
    </table>
</div>