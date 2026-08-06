@include('layouts.header',['breadcrumb' => ['Create User'], 'pageTitle' => 'Create User','title' => 'Create User'])

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
            <form method="post" action="{{route('users.store')}}">
                @csrf
                <div class="card m-b-30">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card-body">
                                <h6 class="card-subtitle">Name:</h6>
                                <div class="form-group mb-0">
                                    <input type="text" value="{{old('name')}}" class="form-control" name="name"
                                           id="inputText">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card-body">
                                <h6 class="card-subtitle">Username :</h6>
                                <div class="form-group mb-0">
                                    <input type="text"  value="{{old('username')}}" class="form-control"
                                           name="username"
                                           id="inputText">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card-body">
                                <h6 class="card-subtitle">Password :</h6>
                                <div class="form-group mb-0">
                                    <input type="password" class="form-control" name="password" id="inputText">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card-body">
                                <h6 class="card-subtitle">Role:</h6>
                                <div class="form-group mb-0">
                                    <select
                                        class="form-control" name="role_id">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}">
                                                {{$role->name}}
                                            </option>
                                        @endforeach
                                    </select>
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
<script src="/assets/js/targets.js"></script>
