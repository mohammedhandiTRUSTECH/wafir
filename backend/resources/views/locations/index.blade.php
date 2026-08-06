@include('layouts.header',['breadcrumb' => ['Locations List'], 'pageTitle' => 'Locations List','title' => 'Locations List'])

<!-- Start Contentbar -->
<div class="contentbar">
    @if (session()->has('success'))
        <br>
        <div class="alert alert-success">
            {{session()->get('success')}}
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
                            @foreach($locations as $location)
                                <tr>
                                    <td>{{$id}}</td>
                                    <td>{{$location->name}}</td>
                                    <td>
                                        <a href="{{route('locations.edit',$location)}}">
                                            <i class="fa fa-edit la-2x"></i>
                                        </a>
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
@include('layouts.footer')
