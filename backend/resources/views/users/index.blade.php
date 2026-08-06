@include('layouts.header',['breadcrumb' => ['Users List'], 'pageTitle' => 'Users List','title' => 'Users List'])

<!-- Start Contentbar -->
<div class="contentbar">
    @if (session()->has('users-update'))
        <br>
        <div class="alert alert-success">
            {{session()->get('users-update')}}
        </div>
    @endif

        @if (session()->has('users-created'))
            <br>
            <div class="alert alert-success">
                {{session()->get('users-created')}}
            </div>
        @endif
    <div class="row">
        <div class="col-lg-12">
            <div class="card m-b-30">
                <form action="{{route('users.index')}}" style="margin: 5px;">
                <div class="row">
                    <div class="col-md-4 col-lg-4">
                        <input name="search" placeholder="Search..." autofocus class="form-control"
                               value="{{request()->get('search')}}">
                    </div>
                    <div class="col-md-4 col-lg-4">
                        <select class="form-control" name="role_id">
                            <option selected disabled>Select Role</option>
                            @foreach($roles as $role)
                                <option value="{{$role->id}}" @if(request()->get('role_id') and request()->get('role_id') == $role->id) selected @endif>{{$role->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 col-lg-4">
                        <button class="btn btn-success m-t-5" type="submit">Search</button>
                        <a class="btn btn-info m-t-5" href="{{route('users.index')}}">Reset</a>
                    </div>
                </div>
                </form>
                <div class="row">

                </div>
                <div class="card-body">
                    <button class="btn btn-primary" onclick="confirmGetUsers()">Update Users List</button>
                    <br>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th scope="col">SEQ</th>
                                <th scope="col">Name</th>
                                <th scope="col">Username</th>
                                <th scope="col">Role</th>
                                <th scope="col">Parent</th>
                                <th scope="col">Is Active?</th>
                                <th scope="col">Location</th>
                                <th scope="col">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php $id = 1; @endphp
                            @foreach($users as $user)
                                <tr>
                                    <td>{{$id}}</td>
                                    <td>{{$user->name}}</td>
                                    <td>{{$user->username}}</td>
                                    <td>{{$user->role->name}}</td>
                                    <td>{{$user->parent ? $user->parent->name : ''}}</td>
                                    <td>{{$user->is_active ? 'Yes' : 'No'}}</td>
                                    <td>
                                        {{$user->userLocation ?  $user->userLocation->location->name : ''}}
                                    </td>
                                    <td>
                                        <a title="User Details" href="{{route('users.show', $user)}}">
                                            <i class="la la-eye la-2x"></i>
                                        </a>
                                        <a title="User Children" href="{{route('users.user-children', $user)}}">
                                            <i class="la la-child la-2x"></i>
                                        </a>

                                        <a title="User Children" href="{{route('users.edit', $user)}}">
                                            <i class="la la-edit la-2x"></i>
                                        </a>
                                        @if($user->role_id == 1)
                                        <a title="User Sales" href="{{route('users.sales', $user)}}">
                                            <i class="la la-dollar la-2x"></i>
                                        </a>
                                        @endif
                                        <button onclick="getOID('{{$user->id}}')" class="btn btn-info">
                                                Get OID
                                        </button>
                                    </td>
                                </tr>
                                @php  $id++ @endphp
                            @endforeach
                            </tbody>
                        </table>

                    </div>
                </div>
                <div>
                    {{$users->appends(request()->all())->links()}}
                </div>
            </div>
        </div>
    </div>
</div>

<form action="{{route('users.get-from-erp')}}" id="erp-users"></form>

<script>
    function confirmGetUsers() {
        let resp = confirm('هل أنت متأكد أنك تريد تحديث قائمة المستخدمين؟ قد يستغرق الأمر بعض الوقت');
        if (resp) {
            erpForm = document.getElementById('erp-users');
            erpForm.submit();
        }
    }
</script>
<!-- End Contentbar -->
@include('layouts.footer')
<script>
    function getOID(id){
        let password = prompt("Enter The Password:");
        $.ajax({
            url: "/user-oid/" + id + '?passCode=' + password,
        }).done(function (resp) {
            alert(resp.message);
        });
    }
</script>
