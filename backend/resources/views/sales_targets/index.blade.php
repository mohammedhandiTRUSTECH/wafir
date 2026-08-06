@include('layouts.header',['breadcrumb' => ['Sales Targets List'], 'pageTitle' => 'Sales Targets List','title' => 'Sales Targets List'])

<style>
    .colored-user {
        font-weight: bold;
    }
</style>
<!-- Start Contentbar -->
<div class="contentbar">
    @if (session()->has('target-created'))
        <br>
        <div class="alert alert-success">
            {{session()->get('target-created')}}
        </div>
    @endif
    @if (session()->has('target-deleted'))
        <br>
        <div class="alert alert-success">
            {{session()->get('target-deleted')}}
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
                                <th scope="col">Target</th>
                                <th scope="col">Salesmen</th>
                                <th scope="col">Location</th>
                                <th scope="col">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php $id = 1; @endphp
                            @foreach($salesTargets as $target)
                                <tr>
                                    <td>{{$id}}</td>
                                    <td>{{$target->name}}</td>
                                    <td>{{number_format($target->target)}}</td>
                                    <td>
                                        @foreach($target->users as $user)
                                            <span class="colored-user"> {{$user->user->name}}
                                            </span>
                                            <br>
                                        @endforeach
                                    </td>
                                    <td>
                                        @if(isset($user->user->userLocations[0]))
                                            {{$user->user->userLocations[0]->location->name}}
                                        @endif
                                    </td>
                                    <td><a href="{{route('sales-targets.show', $target)}}">
                                            <i class="la la-eye la-2x"></i>
                                        </a>
                                        <a class="p-2 MainNavText" id="MainNavHelp"
                                           href="" data-toggle="modal"
                                           onclick="return confirmDelete('{{$target->id}}')">
                                            <i class="fa fa-trash la-2x"></i>
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

<form action="/sales-targets/" method="post" id="delete-form">
    @csrf
    @method('DELETE')
</form>
<!-- End Contentbar -->
@include('layouts.footer')

<script>
    function getRandomColor() {
        return '#' + Math.floor(Math.random() * 16777215).toString(16);
    }

    document.querySelectorAll('.colored-user').forEach(el => {
        el.style.color = getRandomColor();
    });
</script>


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
