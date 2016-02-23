@extends('templates.default')

@section('content')

    <div id="content" class="col-sm-10">
        <!-- start: Content -->

        <div>
            <hr>
            <ul class="breadcrumb">
                <li><a href="dashboard">Dashboard</a></li>
                <li><a href="campaigns">Campaigns</a></li>
            </ul>
            <hr>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="box">
                    <div class="box-header" data-original-title>
                        <h2><i class="fa fa-user"></i><span class="break"></span>Campaigns</h2>
                        <div class="box-icon">
                            <a href="table.html#" class="btn-minimize"><i class="fa fa-chevron-up"></i></a>
                            <a href="table.html#" class="btn-close"><i class="fa fa-times"></i></a>
                        </div>
                    </div>
                    <div class="box-content">
                        <table class="table table-striped table-bordered bootstrap-datatable datatable">
                            <thead>
                            <tr>
                                <th>Campaign Name</th>
                                <th>Type</th>
                                <th>Group ID</th>
                                <th>List</th>
                                <th>Segment</th>
                                <th>Status</th>
                                <th>Date Added</th>
                                <th>Send At</th>

                            </tr>
                            </thead>
                            <tbody>

                            @foreach($campaigns AS $row)

                                <tr>
                                    <td>{!! $row['name'] !!}</td>
                                    <td>{!! $row['type'] !!}</td>
                                    <td>{!! $row['group_id'] !!}</td>
                                    <td>{!! $row['listname'] !!}</td>
                                    <td>{!! $row['segmentname'] !!}</td>
                                    <td>{!! $row['status'] !!}</td>
                                    <td>{!! $row['date_added'] !!}</td>
                                    <td>{!! $row['send_at'] !!}</td>
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

