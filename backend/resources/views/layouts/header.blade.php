<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Orbiter is a bootstrap minimal & clean admin template">
    <meta name="keywords" content="admin, admin panel, admin template, admin dashboard, responsive, bootstrap 4, ui kits, ecommerce, web app, crm, cms, html, sass support, scss">
    <meta name="author" content="Themesbox">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <title>{{isset($title) ? 'Fayhaa Almawadah - '. $title : 'Fayhaa Almawadah'}}</title>
    <!-- Fevicon -->
    <link rel="shortcut icon" href="/logo.png">
    <!-- Start css -->
    <!-- Switchery css -->
    <link href="/assets/plugins/switchery/switchery.min.css" rel="stylesheet">
    <!-- Select2 css -->
    <link href="/assets/plugins/select2/select2.min.css" rel="stylesheet" type="text/css">
    <!-- Apex css -->
    <link href="/assets/plugins/apexcharts/apexcharts.css" rel="stylesheet">
    <!-- Slick css -->
    <link href="/assets/plugins/slick/slick.css" rel="stylesheet">
    <link href="/assets/plugins/slick/slick-theme.css" rel="stylesheet">
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="/assets/css/icons.css" rel="stylesheet" type="text/css">
    <link href="/assets/css/flag-icon.min.css" rel="stylesheet" type="text/css">
    <link href="/assets/css/style.css" rel="stylesheet" type="text/css">
    <link href="/assets/css/select2.css" rel="stylesheet" type="text/css">

    <link rel="stylesheet" href="http://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.4/css/select2.min.css" rel="stylesheet" />
    <!-- End css -->
</head>
<body class="vertical-layout">
@include('layouts.sidebar')

<!-- Start Breadcrumbbar -->
<div class="breadcrumbbar">
    <div class="row align-items-center">
        <div class="col-md-8 col-lg-8">
            <h4 class="page-title">{{isset($pageTitle) ? $pageTitle : 'Home'}}</h4>
            <div class="breadcrumb-list">
                @if($breadcrumb)
                    <ol class="breadcrumb">
                        @foreach($breadcrumb as $item)
                            <li class="breadcrumb-item @if($loop->last) active @endif"><a href="#">{{$item}}</a></li>

                        @endforeach
                    </ol>
                @endif
            </div>
        </div>
    </div>
</div>
<!-- End Breadcrumbbar -->
