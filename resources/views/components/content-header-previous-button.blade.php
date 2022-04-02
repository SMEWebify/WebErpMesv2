<div class="row mb-2">
    <div class="col-sm-6">
        <h1> {{ $h1 }}</h1>
    </div>
    <div class="col-sm-6">
        <ul class="nav nav-pills float-sm-right">
            <li class="nav-item"><a class="btn btn-info btn" href="{{ $previous }}"> < </a></li>
            <li class="nav-item"> |  | </li>
            <li class="nav-item"><a class="btn btn-primary btn" href="{{ $list }}">Back to lists</a></li>
            <li class="nav-item"> |  | </li>
            <li class="nav-item"><a class="btn btn-info btn" href="{{ $next }}">></a></li>
        </ul>
    </div>
</div>