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
                        <table class="table table-striped table-bordered bootstrap-datatable datatable" id="groups">
                            <thead>
                            <tr>
                                <th>Group ID</th>
                                <th>Customer ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Started</th>
                                <th>Finished</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($groups AS $row)
                                <tr>
                                    <td><a href="group-emails?id=<?php echo $row['group_email_id']?>"><?php echo $row['group_email_id']?></a></td>
                                    <td>{!!$row['customer_id']!!}</td>
                                    <td>{!!$row['first_name'].' '.$row['last_name']!!}</td>
                                    <td>{!!$row['email']!!}</td>
                                    <td>{!!$row['status']!!}</td>
                                    <td>{!!$row['started_at']!!}</td>
                                    <td>{!!$row['finished_at']!!}</td>
                                    <td style="text-align: center">
                                        <a href="v1/groups/<?php echo $row['group_email_id'];?>/approve" class="btn btn-success" style="vertical-align: middle"><i class="fa fa-check"></i></a>
                                        <a href="v1/groups/<?php echo $row['group_email_id'];?>/pause" class="btn btn-warning" style="vertical-align: middle"><i class="fa fa-pause"></i></a>
                                        <a href="v1/groups/<?php echo $row['group_email_id'];?>/setassent" class="btn btn-danger" style="vertical-align: middle"><i class="fa fa-envelope-o"></i></a>
                                        <a href="v1/groups/<?php echo $row['group_email_id'];?>/resume" class="btn btn-info" style="vertical-align: middle"><i class="fa fa-play"></i></a>
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

