@include('layouts.header',['breadcrumb' => ['Edit User'], 'pageTitle' => 'Edit User','title' => 'Edit User'])

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
            <form method="post" action="{{route('users.update',$user)}}">
                @csrf
                @method('PUT')
                <div class="card m-b-30">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card-body">
                                <h6 class="card-subtitle">Name:</h6>
                                <div class="form-group mb-0">
                                   <input type="text" value="{{$user->name}}" class="form-control" name="name">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card-body">
                                <h6 class="card-subtitle">Username:</h6>
                                <div class="form-group mb-0">
                                    <input type="text" value="{{$user->username}}" class="form-control" name="username">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card-body">
                                <h6 class="card-subtitle">Password:</h6>
                                <div class="form-group mb-0">
                                    <input type="password" class="form-control" name="password">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card-body">
                                <h6 class="card-subtitle">Role:</h6>
                                <div class="form-group mb-0">
                                    <select class="form-control" name="role_id">
                                        @foreach($roles as $role)
                                            <option value="{{$role->id}}"
                                                    @if($role->id == $user->role_id) selected @endif>
                                                {{$role->name}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card-body">
                                <h6 class="card-subtitle">Location:</h6>
                                <div class="form-group mb-0">
                                    <select class="form-control" name="location_id">
                                        <option disabled selected>Select Location</option>
                                        @foreach($locations as $location)
                                            <option value="{{$location->id}}"
                                                    @if($user->userLocation and  $user->userLocation->location_id == $location->id) selected @endif>
                                                {{$location->name}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card-body">
                                <h6 class="card-subtitle">Is Active :</h6>
                                <div class="form-group mb-0">
                                    <input type="checkbox" name="is_active" @if($user->is_active) checked @endif>
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
<script src="/assets/js/targets.js"></script>
