<div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch flex-column">
    <div class="card bg-light d-flex flex-fill">
      <div class="card-header border-bottom-0">
        {{ $label }}
        <button onclick="window.location='{{ route('addresses.edit', ['id' => $id])}}'" class="btn btn-xs btn-default text-primary mx-1 shadow float-right" title="Edit address">
          <i class="fas fa-edit"></i>Edit address
        </button>
      </div>
      <div class="card-body pt-0">
        <div class="row">
          <div class="col-7">
            <ul class="ml-4 mb-0 fa-ul ">
              <li class="small"><span class="fa-li"><i class="fas fa-lg fa-building"></i></span> Adress: {{ $adress }}</li>
              <li class="small"><span class="fa-li"><i class="fas fa-lg fa-align-left"></i></span> Zip code & city: {{ $zipcode }}  {{ $city }}</li>
              <li class="small"><span class="fa-li"><i class="fas fa-lg fa-align-left"></i></span> Country : {{ $county }}</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>