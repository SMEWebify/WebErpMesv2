<div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch flex-column">
    <div class="card bg-light d-flex flex-fill">
      <div class="card-header border-bottom-0">
        {{ $function }} 
        <button onclick="window.location='{{ route('contacts.edit', ['id' => $id])}}'" class="btn btn-xs btn-default text-primary mx-1 shadow float-right" title="Edit contact">
          <i class="fas fa-edit "></i>Edit contact
        </button>
      </div>
      <div class="card-body pt-0">
        <div class="row">
          <div class="col-7">
            <h2 class="lead"><b>{{ $name }}</b> <b>{{ $firstname }}</b></h2>
            <ul class="ml-4 mb-0 fa-ul ">
              <li class="small"><span class="fa-li"><i class="fas fa-lg fa-at"></i></span> E-mail: {{ $mail }}</li>
              <li class="small"><span class="fa-li"><i class="fas fa-lg fa-phone"></i></span> Phone #: {{ $number }}</li>
              <li class="small"><span class="fa-li"><i class="fas fa-lg fa-phone"></i></span> Phone #: {{ $mobile }}</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>