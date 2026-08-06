<div class="infobar-settings-sidebar-overlay"></div>
<!-- End Infobar Setting Sidebar -->
<!-- Start Containerbar -->
<div id="containerbar">
    <!-- Start Leftbar -->
    <div class="leftbar">
        <!-- Start Sidebar -->
        <div class="sidebar">
            <!-- Start Logobar -->
            <div class="logobar">
                <a href="/home" class="logo logo-large"><img src="/logo.png" class="img-fluid" alt="logo"></a>
            </div>
            <!-- End Logobar -->
            <!-- Start Navigationbar -->
            <div class="navigationbar">

                <ul class="vertical-menu">

                    <li>
                        <a href="javaScript:void();">
                            <img src="/assets/images/svg-icon/dashboard.svg" class="img-fluid" alt="dashboard"><span>Locations</span><i
                                class="feather icon-chevron-right pull-right"></i>
                        </a>
                        <ul class="vertical-submenu">
                            <li><a href="{{route('locations.index')}}">All Locations</a></li>
                            <li><a href="{{route('locations.create')}}">Create Location</a></li>
                        </ul>
                    </li>

                </ul>
                <ul class="vertical-menu">

                    <li>
                        <a href="javaScript:void();">
                            <img src="/assets/images/svg-icon/dashboard.svg" class="img-fluid" alt="dashboard"><span>Users</span><i
                                class="feather icon-chevron-right pull-right"></i>
                        </a>
                        <ul class="vertical-submenu">
                            <li><a href="{{route('users.index')}}">All Users</a></li>
                            <li><a href="{{route('users.create')}}">Create User</a></li>
                            <li><a href="{{route('users.hierarchy')}}">Users Hierarchy</a></li>
                            <li><a href="{{route('users.import-sales')}}">Import Users Sales</a></li>
                        </ul>
                    </li>

                </ul>
                <ul class="vertical-menu">

                    <li>
                        <a href="javaScript:void();">
                            <img src="/assets/images/svg-icon/dashboard.svg" class="img-fluid" alt="dashboard"><span>Sales Targets</span><i
                                class="feather icon-chevron-right pull-right"></i>
                        </a>
                        <ul class="vertical-submenu">
                            <li><a href="{{route('sales-targets.index')}}">Sales Targets</a></li>
                            <li><a href="{{route('sales-targets.create')}}">Create Sale Target</a></li>
                        </ul>
                    </li>

                </ul>
                <ul class="vertical-menu">

                    <li>
                        <a href="javaScript:void();">
                            <img src="/assets/images/svg-icon/dashboard.svg" class="img-fluid" alt="dashboard"><span>Roles</span><i
                                class="feather icon-chevron-right pull-right"></i>
                        </a>
                        <ul class="vertical-submenu">
                            <li><a href="{{route('roles.index')}}">Roles</a></li>
                        </ul>
                    </li>

                </ul>
            </div>
            <!-- End Navigationbar -->
        </div>
        <!-- End Sidebar -->
    </div>
    <!-- End Leftbar -->
    <!-- Start Rightbar -->
    <div class="rightbar">
        <!-- Start Topbar Mobile -->
        <div class="topbar-mobile">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <div class="mobile-logobar">
                        <a href="/" class="mobile-logo"><img src="/logo.jpeg" class="img-fluid" alt="logo"></a>
                    </div>
                    <div class="mobile-togglebar">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item">
                                <div class="topbar-toggle-icon">
                                    <a class="topbar-toggle-hamburger" href="javascript:void();">
                                        <img src="/assets/images/svg-icon/horizontal.svg"
                                             class="img-fluid menu-hamburger-horizontal" alt="horizontal">
                                        <img src="/assets/images/svg-icon/verticle.svg"
                                             class="img-fluid menu-hamburger-vertical" alt="verticle">
                                    </a>
                                </div>
                            </li>
                            <li class="list-inline-item">
                                <div class="menubar">
                                    <a class="menu-hamburger" href="javascript:void();">
                                        <img src="/assets/images/svg-icon/collapse.svg"
                                             class="img-fluid menu-hamburger-collapse" alt="collapse">
                                        <img src="/assets/images/svg-icon/close.svg"
                                             class="img-fluid menu-hamburger-close" alt="close">
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- Start Topbar -->
        <div class="topbar">
            <!-- Start row -->
            <div class="row align-items-center">
                <!-- Start col -->
                <div class="col-md-12 align-self-center">
                    <div class="togglebar">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item">
                                <div class="menubar">
                                    <a class="menu-hamburger" href="javascript:void();">
                                        <img src="/assets/images/svg-icon/collapse.svg"
                                             class="img-fluid menu-hamburger-collapse" alt="collapse">
                                        <img src="/assets/images/svg-icon/close.svg"
                                             class="img-fluid menu-hamburger-close" alt="close">
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="infobar">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item">
                                <div class="profilebar">
                                    <div class="dropdown">
                                        <a class="dropdown-toggle" href="#" role="button" id="profilelink"
                                           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img
                                                src="/assets/images/users/profile.svg" class="img-fluid"
                                                alt="profile"><span class="feather icon-chevron-down live-icon"></span></a>
                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="profilelink">
                                            <div class="dropdown-item">
                                                <div class="profilename">
                                                    <h5>{{auth()->user()->name}}</h5>
                                                </div>
                                            </div>
                                            <div class="userbox">
                                                <ul class="list-unstyled mb-0">
                                                    <li class="media dropdown-item">
                                                        <a href="{{'#'}}" class="profile-icon"><img
                                                                src="/assets/images/svg-icon/user.svg" class="img-fluid"
                                                                alt="user">My Profile</a>
                                                    </li>
                                                    <li class="media dropdown-item">
                                                        <a href="{{route('logout')}}"
                                                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                                           class="profile-icon"><img
                                                                src="/assets/images/svg-icon/logout.svg"
                                                                class="img-fluid" alt="logout">Logout</a>
                                                        <form id="logout-form" action="{{ route('logout') }}"
                                                              method="POST" style="display: none;">
                                                            @csrf
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- End col -->
            </div>
            <!-- End row -->
        </div>
        <!-- End Topbar -->


