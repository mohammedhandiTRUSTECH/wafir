@include('layouts.header',['breadcrumb' => ['Create Location'], 'pageTitle' => 'Create Location','title' => 'Create Location'])

<!-- Start Contentbar -->
<div class="contentbar">
    <div class="col-lg-12">
        <div class="col-md-12">
            @if ($errors->any())
                <br>
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="post" action="{{route('locations.store')}}">
                @csrf
                <div class="card m-b-30">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card-body">
                                <h6 class="card-subtitle">Name:</h6>
                                <div class="form-group mb-0">
                                    <input type="text" value="{{old('name')}}" class="form-control" name="name"
                                           id="inputText">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <button type="submit" class="btn btn-primary">Create</button>
            </form>

        </div>
    </div>
</div>

@include('layouts.footer')
