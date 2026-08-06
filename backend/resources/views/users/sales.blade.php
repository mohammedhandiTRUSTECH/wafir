@include('layouts.header', ['breadcrumb' => ['User Sales ' . $user->name], 'pageTitle' => 'User Sales', 'title' => 'User Sales'])

<div class="contentbar">
    <div class="row">
        <div class="col-lg-12">
            <div class="card m-b-30">
                <form action="{{route('users.sales', $user)}}" style="margin: 5px;">
                    <div class="row">
                        <div class="col-md-4 col-lg-4">
                            <label>Start Date</label>
                            <input name="start_date" type="date" max="{{date('Y-m-d')}}" class="form-control"
                                   value="{{request()->get('start_date')}}">
                        </div>
                        <div class="col-md-4 col-lg-4">
                            <label>End Date</label>
                            <input name="end_date" type="date" max="{{date('Y-m-d')}}" class="form-control"
                                   value="{{request()->get('end_date')}}">
                        </div>
                        <div class="col-md-4 col-lg-4">
                            <br>
                            <button class="btn btn-success m-t-5" type="submit">Get Data</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @if( isset($getTotalSales['data']) and  count($getTotalSales['data']))
        <div class="card-body">
            <div class="row">

                <h4>Total Sales is: {{number_format($getTotalSales['data'][0]->TotalSales,2)}}</h4>
                @if($startDate > '2025-08-31')
                <div class="col-lg-12">
                    <div class="card m-b-30">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th scope="col">SEQ</th>
                                    <th scope="col">Invoice Date</th>
                                    <th scope="col">Total Price</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php $id = 1; @endphp
                                @foreach($getTotalSales['data'] as $result)
                                    <tr>
                                        <td>{{$id}}</td>
                                        <td>{{\Illuminate\Support\Carbon::make($result->TransactionDate)->format('Y-m-d H:i')}}</td>
                                        <td>{{number_format($result->NetTotal,2)}}</td>
                                    </tr>
                                    @php  $id++ @endphp
                                @endforeach
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    @endif
</div>
@include('layouts.footer')
