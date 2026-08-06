@include('layouts.header',['breadcrumb' => ['Add Users To Target'], 'pageTitle' => 'Add Users To Target','title' => 'Add Users To Target'])

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
            <form method="post" action="{{route('sales-targets.store-users',$target)}}">
                @csrf
                <div class="card m-b-30">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card-body">
                                <h6 class="card-subtitle">Users Assigned:</h6>
                                <div class="form-group mb-0">
                                    <select
                                        class="select2-multi-select  select2-hidden-accessible form-control"
                                        name="users[]" multiple>

                                        @foreach ($users as $user)
                                            <option
                                                value="{{ $user->id }}" @if(isset($user->target->sales_target_id) and $user->target->sales_target_id == $target->id) selected @endif>
                                                {{$user->name}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
                <button type="submit" class="btn btn-primary">Add Users</button>
            </form>

        </div>
    </div>
</div>

@include('layouts.footer')
