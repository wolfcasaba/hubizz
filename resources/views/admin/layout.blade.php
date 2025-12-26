<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', 'Hubizz Admin') - {{ get_buzzy_config('sitename') }}</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('assets/css/fontawesome.min.css') }}">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Bootstrap 3.3.5 -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/adminlte/bootstrap/css/bootstrap.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/adminlte/dist/css/AdminLTE.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/adminlte/dist/css/skins/_all-skins.min.css') }}">

    <!-- sweetalert -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/adminlte/plugins/sweetalert/sweetalert.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/admin/css/admin.css') }}">

    <!-- Hubizz Custom Styles -->
    <style>
        /* Hubizz branding color */
        .skin-blue .main-header .navbar {
            background-color: #f59e0b;
        }
        .skin-blue .main-header .logo {
            background-color: #d97706;
        }
        .skin-blue .main-header .logo:hover {
            background-color: #b45309;
        }
        .skin-blue .sidebar-menu>li.active>a,
        .skin-blue .sidebar-menu>li:hover>a {
            border-left-color: #f59e0b;
        }

        /* Hubizz section headers */
        .hubizz-section-header {
            padding: 10px 15px;
            background: #fff3cd;
            border-left: 4px solid #f59e0b;
            margin-bottom: 10px;
            font-weight: 600;
            color: #856404;
        }

        /* Chart containers */
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }

        /* Stat boxes customization */
        .hubizz-stat-box {
            border-top: 3px solid #f59e0b;
        }

        /* Badge colors for Hubizz */
        .badge-hubizz {
            background-color: #f59e0b;
        }

        /* Table hover effects */
        .table-hubizz tbody tr:hover {
            background-color: #fffbeb;
        }

        /* Custom buttons */
        .btn-hubizz {
            background-color: #f59e0b;
            color: white;
            border-color: #f59e0b;
        }
        .btn-hubizz:hover {
            background-color: #d97706;
            color: white;
            border-color: #d97706;
        }
    </style>

    @stack('styles')
    @yield('header')
</head>

<body class="hold-transition skin-blue sidebar-mini">
    <div class="wrapper">

        @include('_admin.layout.header')

        <!-- Left side column. contains the logo and sidebar -->
        <aside class="main-sidebar">
            <!-- sidebar: style can be found in sidebar.less -->
            <section class="sidebar">
                <!-- Sidebar user panel -->
                <div class="user-panel">
                    <div class="pull-left image">
                        <img src="{{ makepreview(Auth::user()->icon, 's', 'members/avatar') }}" class="img-circle" alt="User Image">
                    </div>
                    <div class="pull-left info">
                        <p>{{ Auth::user()->username }}</p>
                        <a href="#"><i class="fa fa-circle text-success"></i> {{ trans('admin.Online') }}</a>
                    </div>
                </div>

                <!-- sidebar menu -->
                <ul class="sidebar-menu">
                    <li class="header">{{ trans('admin.MAINNAVIGATION') }}</li>

                    <!-- Dashboard -->
                    <li class="{{request()->route()->named('admin.dashboard') ? 'active' : ''}}">
                        <a href="{{  action('Admin\DashboardController@index') }}">
                            <i class="fa fa-dashboard"></i> <span>{{ trans('admin.dashboard') }}</span>
                        </a>
                    </li>

                    <!-- HUBIZZ SECTION -->
                    <li class="header" style="color: #f59e0b; font-weight: bold;">
                        <i class="fa fa-fire"></i> HUBIZZ FEATURES
                    </li>

                    <!-- Hubizz Affiliate -->
                    <li class="treeview {{request()->route()->named('admin.affiliate.*') ? 'active' : ''}}">
                        <a href="#">
                            <i class="fa fa-money"></i> <span>Affiliate Monetization</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li class="{{ request()->routeIs('admin.affiliate.dashboard') ? 'active' : '' }}">
                                <a href="{{ route('admin.affiliate.dashboard') }}">
                                    <i class="fa fa-dashboard"></i> Dashboard
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.affiliate.networks*') ? 'active' : '' }}">
                                <a href="{{ route('admin.affiliate.networks') }}">
                                    <i class="fa fa-link"></i> Networks
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.affiliate.products*') ? 'active' : '' }}">
                                <a href="{{ route('admin.affiliate.products') }}">
                                    <i class="fa fa-shopping-cart"></i> Products
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.affiliate.links') ? 'active' : '' }}">
                                <a href="{{ route('admin.affiliate.links') }}">
                                    <i class="fa fa-external-link"></i> Links
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('admin.affiliate.analytics') ? 'active' : '' }}">
                                <a href="{{ route('admin.affiliate.analytics') }}">
                                    <i class="fa fa-line-chart"></i> Analytics
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Daily Izz -->
                    <li class="{{ request()->routeIs('admin.hubizz.daily-izz*') ? 'active' : '' }}">
                        <a href="{{ route('admin.hubizz.daily-izz') }}">
                            <i class="fa fa-fire"></i> <span>Daily Izz</span>
                        </a>
                    </li>

                    <!-- Trending Topics -->
                    <li class="{{ request()->routeIs('admin.hubizz.trending*') ? 'active' : '' }}">
                        <a href="{{ route('admin.hubizz.trending') }}">
                            <i class="fa fa-line-chart"></i> <span>Trending Topics</span>
                        </a>
                    </li>

                    <!-- RSS Feeds -->
                    <li class="{{ request()->routeIs('admin.hubizz.rss-feeds*') ? 'active' : '' }}">
                        <a href="{{ route('admin.hubizz.rss-feeds') }}">
                            <i class="fa fa-rss"></i> <span>RSS Feeds</span>
                        </a>
                    </li>

                    <!-- AI Content -->
                    <li class="{{ request()->routeIs('admin.hubizz.ai-content*') ? 'active' : '' }}">
                        <a href="{{ route('admin.hubizz.ai-content') }}">
                            <i class="fa fa-magic"></i> <span>AI Content</span>
                        </a>
                    </li>

                    <!-- Divider -->
                    <li class="header">BUZZY CORE</li>

                    <!-- Inbox -->
                    @if(get_buzzy_config('p_buzzycontact') == 'on')
                    <li class="{{request()->route()->named('admin.mailbox') ? 'active' : ''}}">
                        <a href="{{ route('admin.mailbox') }}">
                            <i class="fa fa-envelope"></i> <span>{{ trans('admin.Inbox') }}</span>
                            @if($unapproveinbox >0)
                            <span class="pull-right badge bg-green">{{ $unapproveinbox }}</span>
                            @endif
                        </a>
                    </li>
                    @endif

                    <!-- Themes -->
                    <li @if(request()->route()->named('admin.themes')) class="active" @endif>
                        <a href="{{ route('admin.themes') }}">
                            <i class="fa fa-eye"></i> <span>{{ trans('themes.themes') }}</span>
                        </a>
                    </li>

                    <!-- Categories -->
                    <li @if(request()->route()->named('admin.categories')) class="active" @endif>
                        <a href="{{ route('admin.categories') }}">
                            <i class="fa fa-folder"></i>
                            <span>{{ trans('admin.Categories') }}</span>
                        </a>
                    </li>

                    <!-- Posts -->
                    <li class="{{request()->route()->named('admin.posts') && empty(request()->query('type')) && request()->query('only')=='' ? 'active': ''}}">
                        <a href="{{route('admin.posts')}}">
                            <i class="fa fa-book"></i>
                            <span>{{ trans('admin.LatestPosts') }}</span>
                        </a>
                    </li>

                    <!-- Users -->
                    <li class="treeview {{request()->route()->named('admin.users') ? 'active' : ''}}">
                        <a href="#">
                            <i class="fa fa-users"></i>
                            <span>{{ trans('admin.Users') }}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li><a href="{{route('admin.users')}}"><i class="fa fa-circle-o"></i> {{ trans('admin.Users') }}</a></li>
                            <li><a href="{{route('admin.users', ['only' => 'admins'])}}"><i class="fa fa-circle-o"></i> {{ trans('admin.Admins') }}</a></li>
                        </ul>
                    </li>

                    <!-- Settings -->
                    <li class="treeview @if(request()->route()->named('admin.configs')) active @endif">
                        <a href="{{ route('admin.configs') }}">
                            <i class="fa fa-cog"></i> <span>{{ trans('admin.Settings') }}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li class="{{ request()->query('q')=='' ? 'active' : ''}}">
                                <a href="{{ route('admin.configs') }}">
                                    <i class="fa fa-circle-o"></i> {{ trans('admin.GeneralSettings') }}
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </section>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <h1>
                    @yield('page-title', 'Hubizz Admin')
                    <small>@yield('page-subtitle', '')</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="{{ route('admin.dashboard') }}"><i class="fa fa-dashboard"></i> Home</a></li>
                    @yield('breadcrumb')
                </ol>
            </section>

            <!-- Main content -->
            <section class="content">
                <!-- Flash Messages -->
                @if(session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h4><i class="icon fa fa-check"></i> Success!</h4>
                    {{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h4><i class="icon fa fa-ban"></i> Error!</h4>
                    {{ session('error') }}
                </div>
                @endif

                @if($errors->any())
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h4><i class="icon fa fa-ban"></i> Validation Error!</h4>
                    <ul>
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Page Content -->
                @yield('content')
            </section>
        </div><!-- /.content-wrapper -->

        @include('_admin.layout.footer')

    </div><!-- ./wrapper -->

    <!-- jQuery 2.1.4 -->
    <script src="{{ asset('assets/plugins/adminlte/plugins/jQuery/jQuery-2.1.4.min.js') }}"></script>
    <!-- jQuery UI 1.11.4 -->
    <script src="{{ asset('assets/plugins/adminlte/plugins/jQueryUI/jquery-ui.min.js') }}"></script>
    <!-- Bootstrap 3.3.5 -->
    <script src="{{ asset('assets/plugins/adminlte/bootstrap/js/bootstrap.min.js') }}"></script>
    <!-- SlimScroll -->
    <script src="{{ asset('assets/plugins/adminlte/plugins/slimScroll/jquery.slimscroll.min.js') }}"></script>

    <!-- sweetalert -->
    <script src="{{ asset('assets/plugins/adminlte/plugins/sweetalert/sweetalert.min.js') }}"></script>

    <!-- AdminLTE App -->
    <script src="{{ asset('assets/plugins/adminlte/dist/js/adminlte.min.js') }}"></script>

    <script>
        var buzzy_item_id ="{{ config('buzzy.item_id') }}";
        var buzzy_base_url ="{{ route('home') }}";
        var buzzy_current_url ="{{ url()->current() }}";
    </script>

    @stack('scripts')
    @yield('footer_js')

    <script src="{{ asset('assets/admin/js/app.js?v='.config('buzzy.version')) }}"></script>

    @yield('footer')

    <div class="hide">
        <input name="_requesttoken" id="requesttoken" type="hidden" value="{{ csrf_token() }}" />
    </div>

</body>
</html>
