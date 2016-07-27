@extends('templates.default')

@section('content')

    <div id="content" class="col-sm-10">
        <!-- start: Content -->

        <div>
            <hr>
            <ul class="breadcrumb">
                <li><a href="/mhapi/dashboard">Dashboard</a></li>
                <li><a href="/mhapi/servers">Servers</a></li>
            </ul>
            <hr>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="box">
                    <div class="box-header" data-original-title>
                        <h2><i class="fa fa-user"></i><span class="break"></span>Groups</h2>
                        <div class="box-icon">
                            <a href="table.html#" class="btn-minimize"><i class="fa fa-chevron-up"></i></a>
                            <a href="table.html#" class="btn-close"><i class="fa fa-times"></i></a>
                        </div>
                    </div>
                    <div class="box-content">
                        <table class="table table-striped table-bordered bootstrap-datatable datatable" id="groups">
                            <thead>
                            <tr>
                                <th>Server ID</th>
                                <th>Bounce Server ID</th>
                                <th>Name</th>
                                <th>Host Name</th>
                                <th>Use For</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($servers AS $row)
                                <tr>
                                    <td>{!! $row['server_id'] !!}</td>
                                    <td>{!! $row['bounce_server_id']!!}</td>
                                    <td>{!! $row['name']!!}</td>
                                    <td>{!! $row['hostname']!!}</td>
                                    <td>{!! $row['use_for']!!}</td>
                                    <td style="text-align: center">
                                        <a href="/mhapi/server/<?php echo $row['server_id'];?>/edit" class="btn btn-success" style="vertical-align: middle"><i class="fa fa-pencil"></i></a>
                                    </td>
                                </tr>
                            @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
            </div><!--/col-->

        </div><!--/row-->

    </div><!--/content-->

    <hr>
    <!-- page scripts -->


@stop

