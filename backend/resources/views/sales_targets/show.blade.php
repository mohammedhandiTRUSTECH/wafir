@include('layouts.header',['breadcrumb' => ['Sales Targets', $target->name ], 'pageTitle' => 'Sales Target','title' => 'Sales Target'])

<!-- Start Contentbar -->
<div class="contentbar">
    @if (session()->has('target-created'))
        <br>
        <div class="alert alert-success">
            {{session()->get('target-created')}}
        </div>
    @endif
    @if (session()->has('user-added'))
        <br>
        <div class="alert alert-success">
            {{session()->get('user-added')}}
        </div>
    @endif

    @if (session()->has('user-target-deleted'))
        <br>
        <div class="alert alert-success">
            {{session()->get('user-target-deleted')}}
        </div>
    @endif
    <div class="row">
        <div class="col-lg-12">
            <div class="card m-b-30">
                <div class="row">
                    <div class="col-md-6 col-lg-6">
                        <h4 style="margin-left: 5px; margin-top: 5px;">Rules</h4>
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
                                <th scope="col">Sales Percentage</th>
                                <th scope="col">Commission Percentage</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php $id = 1; @endphp
                            @foreach($target->details as $rule)
                                <tr>
                                    <td>{{$id}}</td>
                                    <td>{{$rule->sale_percentage}} %</td>
                                    <td>{{$rule->commission_percentage}} %</td>

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
    <div class="row">
        <div class="col-lg-12">
            <div class="card m-b-30">
                <div class="row">
                    <div class="col-md-6 col-lg-6">
                        <h4 style="margin-left: 5px; margin-top: 5px;">SalesMen</h4>
                    </div>

                    <div class="col-md-4 col-lg-4">
                        <a href="{{route('sales-targets.add-users', $target)}}" class="btn btn-primary">
                            Add Users For This Target
                        </a>
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
                            @foreach($target->users as $user)
                                <tr>
                                    <td>{{$id}}</td>
                                    <td>{{$user->user->name}}</td>
                                    <td>
                                        <a class="p-2 MainNavText" id="MainNavHelp"
                                           href="" data-toggle="modal" onclick="return confirmDelete('{{$user->id}}')">
                                            <i class="la la-trash la-2x"></i></a>
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
</div>
<form action="/user-target/" method="post" id="delete-form">
    @csrf
    @method('DELETE')
</form>
<script>
    function confirmDelete(id) {
        let con = confirm("Are you sure to delete this record?");
        if (con) {
            delete_form = document.getElementById('delete-form');
            delete_form.action = delete_form.action + id;
            delete_form.submit();
        }

    }
</script>
<!-- End Contentbar -->
@include('layouts.footer')
