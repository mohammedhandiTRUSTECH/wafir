@include('layouts.header',['breadcrumb' => ['Roles List'], 'pageTitle' => 'Roles List','title' => 'Roles List'])

<!-- Start Contentbar -->
<div class="contentbar">
    @if (session()->has('target-created'))
        <br>
        <div class="alert alert-success">
            {{session()->get('target-created')}}
        </div>
    @endif
    <div class="row">
        <div class="col-lg-12">
            <div class="card m-b-30">
                <div class="row">
                    <div class="col-md-6 col-lg-6">
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
                            @foreach($roles as $role)
                                <tr>
                                    <td>{{$id}}</td>
                                    <td>{{$role->name}}</td>
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
<!-- End Contentbar -->
@include('layouts.footer')
