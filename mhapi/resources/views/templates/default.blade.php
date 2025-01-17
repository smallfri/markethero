<!DOCTYPE html>
<html lang="en">
<head>
	
	<!-- start: Meta -->
	<meta charset="utf-8">
	<title>Market Hero</title>
	<meta name="description" content="Perfectum Dashboard Bootstrap Admin Template.">
	<meta name="author" content="Łukasz Holeczek">
	<!-- end: Meta -->
	
	<!-- start: Mobile Specific -->
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- end: Mobile Specific -->
	
	<!-- start: CSS -->
	
	<!-- bootstrap -->
	<link href="/mhapi/public/assets/css/bootstrap.min.css" rel="stylesheet">
	
	<!-- page css files -->
	<link href="/mhapi/public/assets/css/font-awesome.min.css" rel="stylesheet">
	<link href="/mhapi/public/assets/css/jquery-ui-1.10.3.custom.min.css" rel="stylesheet">
	<link href="/mhapi/public/assets/css/fullcalendar.css" rel="stylesheet">	
	<link href="/mhapi/public/assets/css/jquery.gritter.css" rel="stylesheet">	
	
	<!-- main style -->
	<link href="/mhapi/public/assets/css/style.min.css" rel="stylesheet">

	<!--[if lt IE 9 ]>
		<link href="/mhapi/public/assets/css/style-ie.css" rel="stylesheet">
	<![endif]-->
	
	<!-- end: CSS -->
	

	<!-- The HTML5 shim, for IE6-8 support of HTML5 elements -->
	<!--[if lt IE 9]>
		
	  	<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
		<script src="/mhapi/public/assets/js/respond.min.js"></script>
		
	<![endif]-->

	<!-- start: Favicon -->
	<link rel="shortcut icon" href="http://localhost:8888/bootstrap/perfectum2/img/favicon.ico">
	<!-- end: Favicon -->
	
		
		
		
</head>

<body>
	
	<!-- start: Header -->
	<div class="navbar">
		<div class="container">
			<button class="navbar-toggle" type="button" data-toggle="collapse" data-target=".sidebar-nav.nav-collapse">
			      <span class="icon-bar"></span>
			      <span class="icon-bar"></span>
			      <span class="icon-bar"></span>
			</button>
			<a class="navbar-brand" href="/dashboard"> <img alt="Perfectum Dashboard" src="/mhapi/public/assets/img/market_hero_logo.jpg" style="height: 30px;"/> <span>Market Hero</span></a>
			<!-- start: Header Menu -->
			<div class="header-nav">
				<ul class="nav navbar-nav pull-right">
					{{--<li class="dropdown hidden-xs">--}}
						{{--<a class="btn dropdown-toggle" data-toggle="dropdown" href="index.html#">--}}
							{{--<i class="fa fa-warning"></i> <span class="label label-important hidden-xs">2</span> <span class="label label-success hidden-xs">11</span>--}}
						{{--</a>--}}
						{{--<ul class="dropdown-menu notifications">--}}
							{{--<li>--}}
								{{--<span class="dropdown-menu-title">You have 11 notifications</span>--}}
							{{--</li>	--}}
                        	{{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<i class="fa fa-user"></i> <span class="message">New user registration</span> <span class="time">1 min</span> --}}
                                {{--</a>--}}
                            {{--</li>--}}
							{{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<i class="fa fa-comment"></i> <span class="message">New comment</span> <span class="time">7 min</span> --}}
                                {{--</a>--}}
                            {{--</li>--}}
							{{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<i class="fa fa-comment"></i> <span class="message">New comment</span> <span class="time">8 min</span> --}}
                                {{--</a>--}}
                            {{--</li>--}}
							{{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<i class="fa fa-comment"></i> <span class="message">New comment</span> <span class="time">16 min</span> --}}
                                {{--</a>--}}
                            {{--</li>--}}
							{{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<i class="fa fa-user"></i> <span class="message">New user registration</span> <span class="time">36 min</span> --}}
                                {{--</a>--}}
                            {{--</li>--}}
							{{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<i class="fa fa-shopping-cart"></i> <span class="message">2 items sold</span> <span class="time">1 hour</span> --}}
                                {{--</a>--}}
                            {{--</li>--}}
							{{--<li class="warning">--}}
                                {{--<a href="index.html#">--}}
									{{--<i class="fa fa-user"></i> <span class="message">User deleted account</span> <span class="time">2 hour</span> --}}
                                {{--</a>--}}
                            {{--</li>--}}
							{{--<li class="warning">--}}
                                {{--<a href="index.html#">--}}
									{{--<i class="fa fa-shopping-cart"></i> <span class="message">Transaction was canceled</span> <span class="time">6 hour</span> --}}
                                {{--</a>--}}
                            {{--</li>--}}
							{{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<i class="fa fa-comment"></i> <span class="message">New comment</span> <span class="time">yesterday</span> --}}
                                {{--</a>--}}
                            {{--</li>--}}
							{{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<i class="fa fa-user"></i> <span class="message">New user registration</span> <span class="time">yesterday</span> --}}
                                {{--</a>--}}
                            {{--</li>--}}
                            {{--<li>--}}
                        		{{--<a class="dropdown-menu-sub-footer">View all notifications</a>--}}
							{{--</li>	--}}
						{{--</ul>--}}
					{{--</li>--}}
					<!-- start: Notifications Dropdown -->
					{{--<li class="dropdown hidden-xs">--}}
						{{--<a class="btn dropdown-toggle" data-toggle="dropdown" href="index.html#">--}}
							{{--<i class="fa fa-tasks"></i> <span class="label label-warning hidden-xs">17</span>--}}
						{{--</a>--}}
						{{--<ul class="dropdown-menu tasks">--}}
							{{--<li>--}}
								{{--<span class="dropdown-menu-title">You have 17 tasks in progress</span>--}}
                        	{{--</li>--}}
							{{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<span class="header">--}}
										{{--<span class="title">iOS Development</span>--}}
										{{--<span class="percent"></span>--}}
									{{--</span>--}}
                                    {{--<div class="taskProgress progressSlim progressYellow">80</div> --}}
                                {{--</a>--}}
                            {{--</li>--}}
                            {{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<span class="header">--}}
										{{--<span class="title">Android Development</span>--}}
										{{--<span class="percent"></span>--}}
									{{--</span>--}}
                                    {{--<div class="taskProgress progressSlim progressYellow">47</div> --}}
                                {{--</a>--}}
                            {{--</li>--}}
                            {{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<span class="header">--}}
										{{--<span class="title">Django Project For Google</span>--}}
										{{--<span class="percent"></span>--}}
									{{--</span>--}}
                                    {{--<div class="taskProgress progressSlim progressYellow">32</div> --}}
                                {{--</a>--}}
                            {{--</li>--}}
							{{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<span class="header">--}}
										{{--<span class="title">SEO for new sites</span>--}}
										{{--<span class="percent"></span>--}}
									{{--</span>--}}
                                    {{--<div class="taskProgress progressSlim progressYellow">63</div> --}}
                                {{--</a>--}}
                            {{--</li>--}}
                            {{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<span class="header">--}}
										{{--<span class="title">New blog posts</span>--}}
										{{--<span class="percent"></span>--}}
									{{--</span>--}}
                                    {{--<div class="taskProgress progressSlim progressYellow">80</div> --}}
                                {{--</a>--}}
                            {{--</li>--}}
							{{--<li>--}}
                        		{{--<a class="dropdown-menu-sub-footer">View all tasks</a>--}}
							{{--</li>	--}}
						{{--</ul>--}}
					{{--</li>--}}
					{{--<!-- end: Notifications Dropdown -->--}}
					{{--<!-- start: Message Dropdown -->--}}
					{{--<li class="dropdown hidden-xs">--}}
						{{--<a class="btn dropdown-toggle" data-toggle="dropdown" href="index.html#">--}}
							{{--<i class="fa fa-envelope"></i> <span class="label label-success hidden-xs">9</span>--}}
						{{--</a>--}}
						{{--<ul class="dropdown-menu messages">--}}
							{{--<li>--}}
								{{--<span class="dropdown-menu-title">You have 9 messages</span>--}}
							{{--</li>	--}}
                        	{{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<span class="avatar"><img src="/mhapi/public/assets/img/avatar.jpg" alt="Avatar"></span>--}}
									{{--<span class="header">--}}
										{{--<span class="from">--}}
									    	{{--Łukasz Holeczek--}}
									     {{--</span>--}}
										{{--<span class="time">--}}
									    	{{--6 min--}}
									    {{--</span>--}}
									{{--</span>--}}
                                    {{--<span class="message">--}}
                                        {{--Lorem ipsum dolor sit amet consectetur adipiscing elit, et al commore--}}
                                    {{--</span>  --}}
                                {{--</a>--}}
                            {{--</li>--}}
                            {{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<span class="avatar"><img src="/mhapi/public/assets/img/avatar2.jpg" alt="Avatar"></span>--}}
									{{--<span class="header">--}}
										{{--<span class="from">--}}
									    	{{--Megan Abott--}}
									     {{--</span>--}}
										{{--<span class="time">--}}
									    	{{--56 min--}}
									    {{--</span>--}}
									{{--</span>--}}
                                    {{--<span class="message">--}}
                                        {{--Lorem ipsum dolor sit amet consectetur adipiscing elit, et al commore--}}
                                    {{--</span>  --}}
                                {{--</a>--}}
                            {{--</li>--}}
                            {{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<span class="avatar"><img src="/mhapi/public/assets/img/avatar3.jpg" alt="Avatar"></span>--}}
									{{--<span class="header">--}}
										{{--<span class="from">--}}
									    	{{--Kate Ross--}}
									     {{--</span>--}}
										{{--<span class="time">--}}
									    	{{--3 hours--}}
									    {{--</span>--}}
									{{--</span>--}}
                                    {{--<span class="message">--}}
                                        {{--Lorem ipsum dolor sit amet consectetur adipiscing elit, et al commore--}}
                                    {{--</span>  --}}
                                {{--</a>--}}
                            {{--</li>--}}
							{{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<span class="avatar"><img src="/mhapi/public/assets/img/avatar4.jpg" alt="Avatar"></span>--}}
									{{--<span class="header">--}}
										{{--<span class="from">--}}
									    	{{--Julie Blank--}}
									     {{--</span>--}}
										{{--<span class="time">--}}
									    	{{--yesterday--}}
									    {{--</span>--}}
									{{--</span>--}}
                                    {{--<span class="message">--}}
                                        {{--Lorem ipsum dolor sit amet consectetur adipiscing elit, et al commore--}}
                                    {{--</span>  --}}
                                {{--</a>--}}
                            {{--</li>--}}
                            {{--<li>--}}
                                {{--<a href="index.html#">--}}
									{{--<span class="avatar"><img src="/mhapi/public/assets/img/avatar5.jpg" alt="Avatar"></span>--}}
									{{--<span class="header">--}}
										{{--<span class="from">--}}
									    	{{--Jane Sanders--}}
									     {{--</span>--}}
										{{--<span class="time">--}}
									    	{{--Jul 25, 2012--}}
									    {{--</span>--}}
									{{--</span>--}}
                                    {{--<span class="message">--}}
                                        {{--Lorem ipsum dolor sit amet consectetur adipiscing elit, et al commore--}}
                                    {{--</span>  --}}
                                {{--</a>--}}
                            {{--</li>--}}
							{{--<li>--}}
                        		{{--<a class="dropdown-menu-sub-footer">View all messages</a>--}}
							{{--</li>	--}}
						{{--</ul>--}}
					{{--</li>--}}
					{{--<!-- end: Message Dropdown -->--}}
					{{--<li>--}}
						{{--<a class="btn" href="index.html#">--}}
							{{--<i class="fa fa-wrench"></i>--}}
						{{--</a>--}}
					{{--</li>--}}
					<!-- start: User Dropdown -->
					<li class="dropdown hidden-xs">
						<a class="btn dropdown-toggle" data-toggle="dropdown" href="index.html#">
							<i class="fa fa-user"></i>
							<span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							<li><a href="index.html#"><i class="fa fa-user"></i> Profile</a></li>
							<li><a href="logout"><i class="fa fa-off"></i> Logout</a></li>
						</ul>
					</li>
					<!-- end: User Dropdown -->
				</ul>
			</div>
			<!-- end: Header Menu -->
			
		</div>
	</div>
	<!-- start: Header -->
	
		<div class="container">
		<div class="row">
					<!-- start: Main Menu -->
			<div class="col-sm-2 main-menu-span">
				<div class="sidebar-nav nav-collapse collapse navbar-collapse">
					<ul class="nav nav-tabs nav-stacked main-menu">
						<li><a href="/mhapi/dashboard"><i class="fa fa-home icon"></i><span class="hidden-sm"> Dashboard</span></a></li>
						<li><a href="/mhapi/controls"><i class="fa fa-cogs icon"></i><span class="hidden-sm"> Controls</span></a></li>
						<li><a href="/mhapi/servers"><i class="fa fa-bars icon"></i><span class="hidden-sm"> Servers</span></a></li>
						<li><a href="/mhapi/trace-logs"><i class="fa fa-pencil icon"></i><span class="hidden-sm"> API Logs</span></a></li>
						<li><a href="/mhapi/application-logs"><i class="fa fa-pencil icon"></i><span class="hidden-sm"> Application Logs</span></a></li>
						{{--<li><a href="ui.html"><i class="fa fa-eye"></i><span class="hidden-sm"> UI Features</span></a></li>--}}
						{{--<li><a href="forms.html"><i class="fa fa-edit"></i><span class="hidden-sm"> Forms</span></a></li>--}}
						{{--<li><a href="charts.html"><i class="fa fa-bar-chart-o"></i><span class="hidden-sm"> Charts</span></a></li>--}}
						{{--<li>--}}
							{{--<a class="dropmenu" href="index.html#"><i class="fa fa-folder"></i><span class="hidden-sm"> Dropdown</span></a>--}}
							{{--<ul>--}}
								{{--<li><a class="submenu" href="submenu.html"><i class="fa fa-file"></i><span class="hidden-sm"> Sub Menu 1</span></a></li>--}}
								{{--<li><a class="submenu" href="submenu.html"><i class="fa fa-file"></i><span class="hidden-sm"> Sub Menu 2</span></a></li>--}}
								{{--<li><a class="submenu" href="submenu.html"><i class="fa fa-file"></i><span class="hidden-sm"> Sub Menu 3</span></a></li>--}}
							{{--</ul>	--}}
						{{--</li>--}}
						{{--<li><a href="typography.html"><i class="fa fa-font"></i><span class="hidden-sm"> Typography</span></a></li>--}}
						{{--<li><a href="gallery.html"><i class="fa fa-picture-o"></i><span class="hidden-sm"> Gallery</span></a></li>--}}
						{{--<li><a href="table.html"><i class="fa fa-align-justify"></i><span class="hidden-sm"> Tables</span></a></li>--}}
						{{--<li><a href="calendar.html"><i class="fa fa-calendar"></i><span class="hidden-sm"> Calendar</span></a></li>--}}
						{{--<li><a href="grid.html"><i class="fa fa-th"></i><span class="hidden-sm"> Grid</span></a></li>--}}
						{{--<li><a href="file-manager.html"><i class="fa fa-folder-open"></i><span class="hidden-sm"> File Manager</span></a></li>--}}
						{{--<li>--}}
							{{--<a class="dropmenu" href="index.html#"><i class="fa fa-star"></i><span class="hidden-sm"> Icons</span></a>--}}
							{{--<ul>--}}
								{{--<li><a class="submenu" href="icons-font-awesome.html"><i class="fa fa-star"></i><span class="hidden-sm"> Font Awesome</span></a></li>--}}
								{{--<li><a class="submenu" href="icons-halflings.html"><i class="fa fa-star"></i><span class="hidden-sm"> Halflings</span></a></li>--}}
								{{--<li><a class="submenu" href="icons-glyphicons-pro.html"><i class="fa fa-star"></i><span class="hidden-sm"> Glyphicons PRO</span></a></li>--}}
								{{--<li><a class="submenu" href="icons-filetypes.html"><i class="fa fa-star"></i><span class="hidden-sm"> Filetypes</span></a></li>--}}
								{{--<li><a class="submenu" href="icons-social.html"><i class="fa fa-star"></i><span class="hidden-sm"> Social</span></a></li>--}}
							{{--</ul>	--}}
						{{--</li>--}}
						{{--<li><a href="login.html"><i class="fa fa-lock"></i><span class="hidden-sm"> Login Page</span></a></li>--}}
					</ul>
				</div><!--/.well -->
			</div><!--/col-->
			<!-- end: Main Menu -->
			
			<noscript>
				<div class="alert alert-block col-sm-10">
					<h4 class="alert-heading">Warning!</h4>
					<p>You need to have <a href="http://en.wikipedia.org/wiki/JavaScript" target="_blank">JavaScript</a> enabled to use this site.</p>
				</div>
			</noscript>
			
@yield('content')


		</div><!--/fluid-row-->
				
			<div class="modal fade" id="myModal">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
							<h4 class="modal-title">Modal title</h4>
						</div>
						<div class="modal-body">
							<p>Here settings can be configured...</p>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							<button type="button" class="btn btn-primary">Save changes</button>
						</div>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->
			</div><!-- /.modal -->
		
		<div class="clearfix"></div>
		
		<footer>
			<p>
				<span style="text-align:left;float:left">&copy; 2013 creativeLabs. <a href="http://bootstrapmaster.com">Admin Templates</a> by BootstrapMaster</span>
				<span class="hidden-phone" style="text-align:right;float:right">Powered by: <a href="http://bootstrapmaster.com/demo/perfectum/" alt="Bootstrap Admin Templates">Perfectum Dashboard</a></span>
			</p>
		</footer>
				
	</div><!--/.fluid-container-->

	<!-- start: JavaScript-->
	<!--[if !IE]>-->

			<script src="/mhapi/public/assets/js/jquery-2.0.3.min.js"></script>

	<!--<![endif]-->

	<!--[if IE]>
	
		<script src="/mhapi/public/assets/js/jquery-1.10.2.min.js"></script>
	
	<![endif]-->

	<!--[if !IE]>-->

		<script type="text/javascript">
			window.jQuery || document.write("<script src='/mhapi/public/assets/js/jquery-2.0.3.min.js'>"+"<"+"/script>");
		</script>

	<!--<![endif]-->

	<!--[if IE]>
	
		<script type="text/javascript">
	 	window.jQuery || document.write("<script src='/mhapi/public/assets/js/jquery-1.10.2.min.js'>"+"<"+"/script>");
		</script>
		
	<![endif]-->
	<script src="/mhapi/public/assets/js/jquery-migrate-1.2.1.min.js"></script>
	<script src="/mhapi/public/assets/js/bootstrap.min.js"></script>
	
	
	<!-- page scripts -->
	<script src="/mhapi/public/assets/js/jquery-ui-1.10.3.custom.min.js"></script>
	<script src="/mhapi/public/assets/js/jquery.knob.modified.min.js"></script>
	<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="/mhapi/public/assets/js/excanvas.min.js"></script><![endif]-->
	<script src="/mhapi/public/assets/js/jquery.flot.min.js"></script>
	<script src="/mhapi/public/assets/js/jquery.flot.pie.min.js"></script>
	<script src="/mhapi/public/assets/js/jquery.flot.stack.min.js"></script>
	<script src="/mhapi/public/assets/js/jquery.flot.resize.min.js"></script>
	<script src="/mhapi/public/assets/js/jquery.flot.time.min.js"></script>
	<script src="/mhapi/public/assets/js/jquery.sparkline.min.js"></script>
	<script src="/mhapi/public/assets/js/fullcalendar.min.js"></script>
	{{--<script src="/mhapi/public/assets/js/jquery.gritter.min.js"></script>--}}
	
	<!-- theme scripts -->
	<script src="/mhapi/public/assets/js/default.min.js"></script>
	<script src="/mhapi/public/assets/js/core.min.js"></script>
	
	<!-- inline scripts related to this page -->
	<script src="/mhapi/public/assets/js/pages/index.js"></script>

		<!--subscribers page -->
	<script src="/mhapi/public/assets/js/jquery-ui-1.10.3.custom.min.js"></script>
	<script src="/mhapi/public/assets/js/jquery.dataTables.min.js"></script>
	<script src="/mhapi/public/assets/js/dataTables.bootstrap.min.js"></script>
 <!-- inline scripts related to this page -->
	<script src="/mhapi/public/assets/js/pages/table.js"></script>
		<!--subscribers/-->




</body>
</html>
