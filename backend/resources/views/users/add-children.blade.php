@include('layouts.header',['breadcrumb' => ['Add Children To User: ' . $user->name], 'pageTitle' => 'Add Children To User','title' => 'Add Children To User'])

<!-- Start Contentbar -->
<div class="contentbar">
    <div class="col-lg-12">
        <div class="col-md-12">
            @if(session()->has('success'))
                <div class="alert alert-success">
                    {{session()->get('success')}}
                </div>
            @endif
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
            <form method="post" action="{{route('users.add-user-children',$user)}}">
                @csrf
                <div class="card m-b-30">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card-body">
                                <h6 class="card-subtitle">Choose Children:</h6>
                                <div class="form-group mb-0">
                                    <select
                                            class="select2-multi-select  select2-hidden-accessible form-control"
                                            name="users[]" multiple>

                                        @foreach ($users as $child)
                                            <option value="{{ $child->id }}">
                                                {{$child->name}}
                                            </option>
                                        @endforeach
                                    </select>
                                    <br>
                                    <br>
                                    <button type="submit" class="btn btn-primary">Add</button>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </form>

        </div>
    </div>
    @if(count($user->children))
        <div class="row">
            <div class="col-lg-12">
                <div class="card m-b-30">
                    <div class="row">
                        <div class="col-md-6 col-lg-6">
                            <span>User Children</span>
                        </div>

                        <div class="col-md-4 col-lg-4">
                        </div>
                        <div class="col-md-4 col-lg-4">
                        </div>
                    </div>
                    <div class="card-body">

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th scope="col">SEQ</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php $id = 1; @endphp
                                @foreach($user->children as $child)
                                    <tr>
                                        <td>{{$id}}</td>
                                        <td>{{$child->name}}</td>
                                        <td>
                                            <a  class="p-2 MainNavText" id="MainNavHelp"
                                                href="" data-toggle="modal" onclick="return confirmDelete('{{$child->id}}')"> <i class="la la-trash la-2x"></i></a>
                                        </td>
                                    </tr>
                                    @php  $id++ @endphp
                                @endforeach
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<form action="/remove-child/" method="post" id="delete-form">
    @csrf
    @method('DELETE')
</form>
<script>
    function confirmDelete(id){
        let con = confirm("Are you sure to delete this record?");
        if(con){
            delete_form = document.getElementById('delete-form');
            delete_form.action = delete_form.action + id;
            delete_form.submit();
        }

    }
</script>

@include('layouts.footer')
