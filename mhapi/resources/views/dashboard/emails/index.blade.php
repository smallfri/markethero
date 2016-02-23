@extends('templates.default')

@section('content')

    <div id="content" class="col-sm-10">
        <!-- start: Content -->

        <div>
            <hr>
            <ul class="breadcrumb">
                <li><a href="dashboard">Dashboard</a></li>
                <li><a href="transactional-emails">Transactional Emails</a></li>
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
                                <th>Email ID</th>
                                <th>Email UID</th>
                                <th>To Email</th>
                                <th>From Email</th>
                                <th>Subject</th>
                                <th>Retries</th>
                                <th>Status</th>
                                <th>Date Updated</th>

                            </tr>
                            </thead>
                            <tbody>

                            @foreach($emails AS $row)

                                <tr>
                                    <td>{!! $row['email_id'] !!}</td>
                                    <td>{!! $row['email_uid'] !!}</td>
                                    <td>{!! $row['to_email'] !!}</td>
                                    <td>{!! $row['from_email'] !!}</td>
                                    <td>{!! $row['subject'] !!}</td>
                                    <td>{!! $row['retries'] !!}</td>
                                    <td>{!! $row['status'] !!}</td>
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

