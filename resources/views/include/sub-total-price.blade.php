<div class="table-responsive">
    <table class="table">
        <tr>
            <th style="width:50%">Subtotal:</th>
            <td>{{ $subPrice }} {{ $Factory->curency }} </td>
        </tr>
        @forelse($vatPrice as $key => $value)
        <tr>
            <td>Tax <?= $vatPrice[$key][0] ?> %</td>
            <td colspan="4"><?= $vatPrice[$key][1] ?> {{ $Factory->curency }}</td>
        </tr>
        @empty
        <tr>
            <td>No Tax</td>
            <td> </td>
        </tr>
        @endforelse
        <tr>
            <th>Total:</th>
            <td>{{ $totalPrices }} {{ $Factory->curency }}</td>
        </tr>
    </table>
</div>