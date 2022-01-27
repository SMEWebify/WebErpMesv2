<tr>
    <td>{{ $TechProduct->ORDER }}</td>
    <td>{{ $TechProduct->label }}</td>
    <td>{{ $TechProduct->service['label'] }}</td>
    <td>{{ $TechProduct->seting_time }}</td>
    <td>{{ $TechProduct->unit_time }}</td>
    <td>{{ $TechProduct->unit_cost }}</td>
    <td>{{ $TechProduct->unit_price }}</td>
    <td>
      @if($TechProduct->order_lines_id)
        {{ $TechProduct->status['title'] }}
      @else
        Not for quote
      @endif
    </td>
    <td class=" py-0 align-middle">
      <!-- Modal -->
      @include('include.modals.modal-TechCut-update')
      <!-- End Modal -->
      <div class="btn-group btn-group-sm">
        <a href="#" data-toggle="modal" data-target="#TechnicalCutUpdateModal{{ $TechProduct->id }}" class="btn btn-info"><i class="fa fa-lg fa-fw  fa-edit"></i></a>
      </div>
      <div class="btn-group btn-group-sm">
        <a href="{{ route('task.delete', ['id_type'=> $id_type, 'id_page'=> $id_page, 'id_task' => $TechProduct->id])}}" class="btn btn-danger"><i class="fa fa-lg fa-fw fa-trash"></i></a>
      </div>
    </td>
</tr>
