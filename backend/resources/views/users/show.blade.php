@include('layouts.header',['breadcrumb' => ['User Details'], 'pageTitle' => 'User Details ' . $user->name ,'title' => 'User Details'])

<!-- Start Contentbar -->
<div class="contentbar">
    @if(count($user->userLocations))
        <div class="row">
            <div class="col-lg-12">
                <div class="card m-b-30">
                    <span style="margin-left: 10px; margin-top: 10px; color: #0a6aa1">Locations</span>
                    <div class="card-body">

                        <br>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th scope="col">SEQ</th>
                                    <th scope="col">Location</th>
                                    <th scope="col">Date</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php $id = 1; @endphp
                                @foreach($user->userLocations as $location)
                                    <tr>
                                        <td>{{$id}}</td>
                                        <td>{{$location->location->name}}</td>
                                        <td>{{$location->created_at}}</td>
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
    @if(count($user->userParents))
            <div class="row">
                <div class="col-lg-12">
                    <div class="card m-b-30">
                        <span style="margin-left: 10px; margin-top: 10px; color: #0a6aa1">History</span>
                        <div class="card-body">

                            <br>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th scope="col">SEQ</th>
                                        <th scope="col">Parent</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Total Sales</th>
                                        <th scope="col">Last Target</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @php $id = 1; @endphp
                                    @foreach($user->userParents as $parent)
                                        <tr>
                                            <td>{{$id}}</td>
                                            <td>{{$parent->parent->name}}</td>
                                            <td>{{$parent->start_date}}</td>
                                            <td>{{$parent->total_sales > 0 ? number_format($parent->total_sales,2) : ''}}</td>
                                            <td>{{$parent->target >  0 ? number_format($parent->target,2) : ''}}</td>
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
<!-- End Contentbar -->
@include('layouts.footer')
