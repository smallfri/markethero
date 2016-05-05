@extends('templates.default')

@section('content')

    <div id="content" class="col-sm-10">
        <!-- start: Content -->

        <div>
            <hr>
            <ul class="breadcrumb">
                <li><a href="/dashboard">Dashboard</a></li>
            </ul>
            <hr>
        </div>

        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

        <script type="text/javascript">
            google.charts.load('current', {'packages': ['line', 'corechart', 'bar']});
            google.charts.setOnLoadCallback(drawChart);
            google.charts.setOnLoadCallback(drawChart2);
            google.charts.setOnLoadCallback(drawChart3);
            google.charts.setOnLoadCallback(drawChart4);
            function drawChart() {
                var data = new google.visualization.DataTable();
                data.addColumn('string', 'Day');
                data.addColumn('number', 'This Week');
                data.addColumn('number', 'Last Week');
                data.addRows([
                    <?php echo $delivery_stats; ?>

                  ]);
                var options = {
                    backgroundColor: '#fcfcfc',
                    colors: ['blue', 'red'],
                    chart: {
                        title: 'Delivery Rate Over the Last 2 Weeks',
//                    subtitle: 'in millions of dollars (USD)',
                    },
                    width: '100%',
                };
                var chart = new google.charts.Line(document.getElementById('chart_div'));
                chart.draw(data, google.charts.Line.convertOptions(options));
            }
            function drawChart2() {
                var data = new google.visualization.DataTable();
                data.addColumn('string', 'Day');
                data.addColumn('number', 'This Week');
                data.addColumn('number', 'Last Week');
                data.addRows([
                    <?php echo $bounce_stats; ?>

                  ]);
                var options = {
                    backgroundColor: '#fcfcfc',
                    colors: ['blue', 'red'],
                    chart: {
                        title: 'Bounce Rate Over the Last 2 Weeks',
                        //                    subtitle: 'in millions of dollars (USD)',
                    },
                    width: '100%',
                };
                var chart = new google.charts.Line(document.getElementById('chart_div2'));
                chart.draw(data, google.charts.Line.convertOptions(options));
            }
            function drawChart3() {
                var data = new google.visualization.DataTable();
                data.addColumn('string', 'Day');
                data.addColumn('number', 'This Week');
                data.addColumn('number', 'Last Week');
                data.addRows([
                    <?php echo $abuse_stats; ?>

                  ]);
                var options = {
                    backgroundColor: '#fcfcfc',
                    colors: ['blue', 'red'],
                    chart: {
                        title: 'Abuse Rate Over the Last 2 Weeks',
                        //                    subtitle: 'in millions of dollars (USD)',
                    },
                    width: '100%',
                };
                var chart = new google.charts.Line(document.getElementById('chart_div3'));
                chart.draw(data, google.charts.Line.convertOptions(options));
            }
            function drawChart4() {
                var data = new google.visualization.DataTable();
                data.addColumn('string', 'Month');
                data.addColumn('number', 'Subscribers');
                data.addRows([
                    <?php echo $monthly_subscriptions; ?>
                ]);

                var options = {
                    title: 'Subscribers By Month',
                    width: '100%',
                    hAxis: {
                        format: 'M/d/yy',
                        gridlines: {count: 15}
                    },
                    vAxis: {
                        gridlines: {color: 'none'},
                        minValue: 0
                    }
                };
                var chart = new google.charts.Bar(document.getElementById('chart_div4'));
                chart.draw(data, options);
                var button = document.getElementById('change');
                button.onclick = function () {

                    // If the format option matches, change it to the new option,
                    // if not, reset it to the original format.
                    options.hAxis.format === 'M/d/yy' ?
                            options.hAxis.format = 'MMM dd, yyyy' :
                            options.hAxis.format = 'M/d/yy';
                    chart.draw(data, options);
                };
            }

        </script>

        <div class="row">

            <div class="col-sm-2 col-xs-6">
                <a class="quick-button" href="subscribers"  target="_blank">
                    <i class="fa  fa-users"></i>

                    <p>Subscribers</p>
                    <span class="notification">{!! $subscribers !!}</span>
                </a>
            </div>
            <!--/col-->
            <div class="col-sm-2 col-xs-6">
                <a class="quick-button" href="subscribers" target="_blank">
                    <i class="fa   fa-user"></i>

                    <p>Unique Subscribers</p>
                    <span class="notification">{!! $unigue_subscribers !!}</span>
                </a>
            </div><!--/col-->

            <div class="col-sm-2 col-xs-6">
                <a class="quick-button" href="campaigns"  target="_blank">
                    <i class="fa fa-gamepad"></i>

                    <p>Campaigns</p>
                    <span class="notification red">{!! $campaigns !!}</span>
                </a>
            </div><!--/col-->

            <div class="col-sm-2 col-xs-6">
                <a class="quick-button">
                    <i class="fa  fa-list"></i>

                    <p>Lists</p>
                    <span class="notification">{!! $lists !!}</span>

                </a>
            </div><!--/col-->

            <div class="col-sm-2 col-xs-6">
                <a class="quick-button" href="transactional-emails"  target="_blank">
                    <i class="fa  fa-envelope"></i>

                    <p>Transactional Emails</p>
                    <span class="notification">{!! $transactionals !!}</span>

                </a>
            </div><!--/col-->
            <div class="col-sm-2 col-xs-6">
                <a class="quick-button">
                    <i class="fa  fa-puzzle-piece"></i>

                    <p>Segments</p>
                    <span class="notification">{!! $segments !!}</span>

                </a>
            </div><!--/col-->

        </div>
        <hr>
        <div class="row">
            <div class="col-md-4 col-sm-6">
                <div class="box">
                    <div class="box-header">
                        <h2><i class="fa fa-list"></i><span class="break"></span>Deliveries</h2></div>
                    <div class="box-content">

                        <div id="chart_div"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="box">
                    <div class="box-header">
                        <h2><i class="fa fa-list"></i><span class="break"></span>Bounces</h2></div>
                    <div class="box-content">

                        <div id="chart_div2"></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-6">
                <div class="box">
                    <div class="box-header">
                        <h2><i class="fa fa-list"></i><span class="break"></span>Abuse Reports</h2></div>
                    <div class="box-content">

                        <div id="chart_div3"></div>
                    </div>
                </div>
            </div>
        </div><!--/row-->
        <hr>
        <div class="row">

            <div class="col-md-12 col-sm-3">
                <div class="box">
                    <div class="box-header">
                        <h2><i class="fa fa-list"></i><span class="break"></span>Monthly Stats</h2></div>
                    <div class="box-content">

                        <div id="chart_div4"></div>
                    </div>
                </div>
            </div>
        </div><!--/row-->

        <hr>

    </div><!--/content-->

    <hr>

@stop