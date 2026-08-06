@include('layouts.header',['breadcrumb' => ['Create Sale Target'], 'pageTitle' => 'Create Sale Target','title' => 'Create Sale Target'])

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
            <form method="post" action="{{route('sales-targets.store')}}">
                @csrf
                <input type="hidden" name="all_rules" id="all_rules">
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
                                <h6 class="card-subtitle">Target Amount :</h6>
                                <div class="form-group mb-0">
                                    <input type="number" min="1" value="{{old('target')}}" class="form-control"
                                           name="target"
                                           id="inputText">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card-body">
                                <h6 class="card-subtitle">Users Assigned:</h6>
                                <div class="form-group mb-0">
                                    <select
                                        class="select2-multi-select  select2-hidden-accessible form-control"
                                        name="users[]" multiple>

                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">
                                                {{$user->name}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h4 style="margin-left: 10px;">Commission Rules</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card-body">
                                <h6 class="card-subtitle">Sales Percentage:</h6>
                                <div class="form-group mb-0">
                                    <input type="number" min="1" max="100" value="{{old('sales_percentage')}}" class="form-control"
                                           name="sales_percentage"
                                           id="inputText">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card-body">
                                <h6 class="card-subtitle">Commission Percentage:</h6>
                                <div class="form-group mb-0">
                                    <input type="number" step="0.01" min="0.00" max="100" value="{{old('commission_percentage')}}" class="form-control"
                                           name="commission_percentage"
                                           id="inputText">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="commission_rules">

                    </div>

                    <div class="row" style="margin-left: 5px; margin-bottom: 10px">
                        <div class="col-md-3">
                            <button type="button" onclick="addRule()" class="btn btn-info">Add New Rule</button>
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
