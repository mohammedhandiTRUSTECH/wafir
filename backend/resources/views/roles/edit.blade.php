@include('layouts.header',['breadcrumb' => ['Edit Role Commission'], 'pageTitle' => 'Edit Role Commission','title' => 'Edit Role Commission'])

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
            <form method="post" action="{{route('roles.update',$role)}}">
                @csrf
                @method('PUT')
                <input type="hidden" name="all_rules" id="all_rules">
                <div class="card m-b-30">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card-body">
                                <h6 class="card-subtitle">Commission Percentage:</h6>
                                <div class="form-group mb-0">
                                    <input min="0.05" max="100" step="0.05" type="number"
                                           value="{{$role->commission ? $role->commission->commission_percentage : ''}}" class="form-control" name="commission_percentage"
                                           id="inputText">
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>

        </div>
    </div>
</div>

@include('layouts.footer')
