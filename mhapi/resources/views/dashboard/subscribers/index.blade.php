@extends('templates.default')

@section('content')

    <div id="content" class="col-sm-10">
        <!-- start: Content -->

        <div>
            <hr>
            <ul class="breadcrumb">
                <li><a href="dashboard">Dashboard</a></li>
                <li><a href="subscribers">Subscribers</a></li>
            </ul>
            <hr>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="box">
                    <div class="box-header" data-original-title>
                        <h2><i class="fa fa-user"></i><span class="break"></span>Subscribers</h2>
                        <div class="box-icon">
                            <a href="table.html#" class="btn-minimize"><i class="fa fa-chevron-up"></i></a>
                            <a href="table.html#" class="btn-close"><i class="fa fa-times"></i></a>
                        </div>
                    </div>
                    <div class="box-content">
                        <table class="table table-striped table-bordered bootstrap-datatable datatable">
                            <thead>
                            <tr>
                                <th>Subscriber ID</th>
                                <th>Subscriber UID</th>
                                <th>List ID</th>
                                <th>Email</th>
                                <th>IP</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Date Added</th>
                                <th>Date Updated</th>

                            </tr>
                            </thead>
                            <tbody>

                            @foreach($subscribers AS $row)

                                <tr>
                                    <td>{!! $row['subscriber_id'] !!}</td>
                                    <td>{!! $row['subscriber_uid'] !!}</td>
                                    <td>{!! $row['list_id'] !!}</td>
                                    <td>{!! $row['email'] !!}</td>
                                    <td>{!! $row['ip_address'] !!}</td>
                                    <td>{!! $row['source'] !!}</td>
                                    <td>{!! $row['status'] !!}</td>
                                    <td>{!! $row['date_added'] !!}</td>
                                    <td>{!! $row['last_updated'] !!}</td>
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

