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
                        <h2><i class="fa fa-user"></i><span class="break"></span>Groups</h2>
                        <div class="box-icon">
                            <a href="table.html#" class="btn-minimize"><i class="fa fa-chevron-up"></i></a>
                            <a href="table.html#" class="btn-close"><i class="fa fa-times"></i></a>
                        </div>
                    </div>
                    <div class="box-content">
                        <table class="table table-striped table-bordered bootstrap-datatable datatable">
                            <thead>
                            <tr>
                                <th>Group ID</th>
                                <th>Group UID</th>
                                <th>Customer ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Date Added</th>

                            </tr>
                            </thead>
                            <tbody>

                            @foreach($groups AS $row)

                                <tr>
                                    <td>{!! $row['group_email_id'] !!}</td>
                                    <td>{!! $row['group_email_uid'] !!}</td>
                                    <td>{!! $row['customer_id'] !!}</td>
                                    <td>{!! $row['first_name'].' '.$row['last_name'] !!}</td>
                                    <td>{!! $row['email'] !!}</td>
                                    <td>{!! $row['status'] !!}</td>
                                    <td>{!! $row['date_added'] !!}</td>
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

