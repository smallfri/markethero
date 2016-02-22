@extends('templates.default')

@section('content')

    <div id="content" class="col-sm-10">
        <!-- start: Content -->

        <div>
            <hr>
            <ul class="breadcrumb">
                <li><a href="index.html#">Home</a></li>
                <li><a href="index.html#">Dashboard</a></li>
            </ul>
            <hr>
        </div>

        <div class="row circleStats">

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="circleStatsItem red">
                    <i class="fa fa-check"></i>
                    <span class="plus">+</span>
                    <span class="percent">%</span>
                    <input type="text" value="{!! $deliveries_percent !!}" class="orangeCircle"/>
                </div>
                <div class="box-small-title">Delivery Increase</div>
            </div><!--/col-->

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="circleStatsItem blue">
                    <i class="fa  fa-thumbs-o-down"></i>
                    <span class="plus">+</span>
                    <span class="percent">%</span>
                    <input type="text" value="{!! $bounce_percent !!}" class="blueCircle"/>
                </div>
                <div class="box-small-title">Bounce Increase</div>
            </div><!--/col-->

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="circleStatsItem yellow">
                    <i class="fa  fa-envelope-o"></i>
                    <span class="plus">+</span>
                    <span class="percent">%</span>
                    <input type="text" value="{!! $campaigns_precent !!}" class="yellowCircle"/>
                </div>
                <div class="box-small-title">Campaigns Increase</div>
            </div><!--/col-->

            {{--<div class="col-md-2 col-sm-4 col-xs-6">--}}
                {{--<div class="circleStatsItem pink">--}}
                    {{--<i class="fa  fa-globe"></i>--}}
                    {{--<span class="plus">+</span>--}}
                    {{--<span class="percent">%</span>--}}
                    {{--<input type="text" value="23" class="pinkCircle"/>--}}
                {{--</div>--}}
                {{--<div class="box-small-title">Visits</div>--}}
            {{--</div><!--/col-->--}}

            {{--<div class="col-md-2 col-sm-4 col-xs-6">--}}
                {{--<div class="circleStatsItem green">--}}
                    {{--<i class="fa  fa-bar-chart-o"></i>--}}
                    {{--<span class="plus">+</span>--}}
                    {{--<span class="percent">%</span>--}}
                    {{--<input type="text" value="34" class="greenCircle"/>--}}
                {{--</div>--}}
                {{--<div class="box-small-title">Income</div>--}}
            {{--</div><!--/col-->--}}

            {{--<div class="col-md-2 col-sm-4 col-xs-6">--}}
                {{--<div class="circleStatsItem lightorange">--}}
                    {{--<i class="fa  fa-shopping-cart"></i>--}}
                    {{--<span class="plus">+</span>--}}
                    {{--<span class="percent">%</span>--}}
                    {{--<input type="text" value="42" class="lightOrangeCircle"/>--}}
                {{--</div>--}}
                {{--<div class="box-small-title">Sales</div>--}}
            {{--</div><!--/col-->--}}

        </div><!--/row-->

        <hr>

        <div class="row">

            {{--<div class="col-md-8 col-sm-12">--}}
                {{--<div class="box">--}}
                    {{--<div class="box-header">--}}
                        {{--<h2><i class="fa fa-signal"></i><span class="break"></span>Site Statistics</h2>--}}
                        {{--<div class="box-icon">--}}
                            {{--<a href="index.html#" class="btn-setting"><i class="fa fa-wrench"></i></a>--}}
                            {{--<a href="index.html#" class="btn-minimize"><i class="fa fa-chevron-up"></i></a>--}}
                            {{--<a href="index.html#" class="btn-close"><i class="fa fa-times"></i></a>--}}
                        {{--</div>--}}
                    {{--</div>--}}
                    {{--<div class="box-content">--}}
                        {{--<div id="stats-chart" class="center" style="height:300px"></div>--}}
                    {{--</div>--}}
                {{--</div>--}}
            {{--</div>--}}

            <div class="col-md-4 col-sm-12">
                <div class="box">
                    <div class="box-header">
                        <h2><i class="fa fa-list"></i><span class="break"></span>Weekly Stats</h2>
                        <div class="box-icon">
                            <a href="index.html#" class="btn-minimize"><i class="fa fa-chevron-up"></i></a>
                            <a href="index.html#" class="btn-close"><i class="fa fa-times"></i></a>
                        </div>
                    </div>
                    <div class="box-content">
                        <div class="sparkLineStats">

                            <ul class="unstyled">
                                <li>
                                    <span class="sparkLineStats1 "></span>
                                    Deliveries:
                                    <span class="number">{!!$deliveries!!}</span>
                                </li>
                                <li>
                                    <span class="sparkLineStats2"></span>
                                    Bounces:
                                    <span class="number">{!!$bounces!!}</span>
                                </li>
                                <li><span class="sparkLineStats3"></span>
                                    Abuse Reports:
                                    <span class="number">{!!$abuses!!}</span>
                                </li>
                                <li><span class="sparkLineStats4"></span>
                                    Campaigns:
                                    <span class="number">{!! $campaigns !!}</span>
                                </li>
                                <li><span class="sparkLineStats5"></span>
                                    Lists:
                                    <span class="number">{!! $lists !!}</span>
                                </li>
                                <li><span class="sparkLineStats6"></span>
                                    Subscribers: <span class="number">{!! $subscribers !!}</span>
                                </li>
                                {{--<li><span class="sparkLineStats7"></span>--}}
                                    {{--% New Visits:--}}
                                    {{--<span class="number">70,79%</span>--}}
                                {{--</li>--}}
                                {{--<li><span class="sparkLineStats8"></span>--}}
                                    {{--% Returning Visitor:--}}
                                    {{--<span class="number">29,21%</span>--}}
                                {{--</li>--}}

                            </ul>

                        </div><!-- End .sparkStats -->
                    </div>
                </div>

            </div><!--/col-->

        </div><!--/row-->

        <hr>

        {{--<div class="row">--}}

            {{--<div class="col-sm-2 col-xs-6">--}}
                {{--<a class="quick-button">--}}
                    {{--<i class="fa  fa-group"></i>--}}

                    {{--<p>Users</p>--}}
                    {{--<span class="notification">1.367</span>--}}
                {{--</a>--}}
            {{--</div><!--/col-->--}}

            {{--<div class="col-sm-2 col-xs-6">--}}
                {{--<a class="quick-button">--}}
                    {{--<i class="fa  fa-comments-o"></i>--}}

                    {{--<p>Comments</p>--}}
                    {{--<span class="notification green">167</span>--}}
                {{--</a>--}}
            {{--</div><!--/col-->--}}

            {{--<div class="col-sm-2 col-xs-6">--}}
                {{--<a class="quick-button">--}}
                    {{--<i class="fa  fa-shopping-cart"></i>--}}

                    {{--<p>Orders</p>--}}
                {{--</a>--}}
            {{--</div><!--/col-->--}}

            {{--<div class="col-sm-2 col-xs-6">--}}
                {{--<a class="quick-button">--}}
                    {{--<i class="fa  fa-barcode"></i>--}}

                    {{--<p>Products</p>--}}
                {{--</a>--}}
            {{--</div><!--/col-->--}}

            {{--<div class="col-sm-2 col-xs-6">--}}
                {{--<a class="quick-button">--}}
                    {{--<i class="fa  fa-envelope"></i>--}}

                    {{--<p>Messages</p>--}}
                {{--</a>--}}
            {{--</div><!--/col-->--}}

            {{--<div class="col-sm-2 col-xs-6">--}}
                {{--<a class="quick-button">--}}
                    {{--<i class="fa  fa-calendar"></i>--}}

                    {{--<p>Calendar</p>--}}
                    {{--<span class="notification red">68</span>--}}
                {{--</a>--}}
            {{--</div><!--/col-->--}}

        {{--</div><!--/row-->--}}



        <!-- end: Content -->
    </div><!--/#content.span10-->
@stop