<tr>
    <td>{{ $BOMProduct->ORDER }}</td>
    <td>{{ $BOMProduct->LABEL }}</td>
    <td>{{ $BOMProduct->service['LABEL'] }}</td>
    <td>{{ $BOMProduct->Component['CODE'] }}</td>
    <td>{{ $BOMProduct->QTY }}</td>
    <td>{{ $BOMProduct->UNIT_COST }}</td>
    <td>{{ $BOMProduct->UNIT_PRICE }}</td>
    <td class=" py-0 align-middle">
      <div class="btn-group btn-group-sm">
        <a href="#" class="btn btn-info"><i class="fa fa-lg fa-fw fa-edit"></i></a>
      </div>
      <div class="btn-group btn-group-sm">
        <a href="#" class="btn btn-danger"><i class="fa fa-lg fa-fw fa-trash"></i></a>
      </div>
    </td>
  </tr>