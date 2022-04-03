        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success')}}
            </div>
        @endif
        @if($errors->count())
            <div class="alert alert-danger">
                <ul>
                @foreach ( $errors->all() as $message)
                <li> {{ $message }}</li>
                @endforeach
                </ul>
            </div>
        @endif