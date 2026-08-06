@include('layouts.header',['breadcrumb' => ['Home'], 'pageTitle' => 'Home Page','title' => 'Home'])
<style>

    .card-body:hover .show-more {
        display: inline;
    }

    .card {
        top: 50px;
    }


</style>
<!-- Start Contentbar -->
<div class="contentbar" style="display: flex; flex-wrap: wrap; gap: 1rem;">

</div>

<!-- End Contentbar -->
@include('layouts.footer')
