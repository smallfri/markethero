@extends('templates.default')

@section('content')

<div id="content" class="col-sm-10">
    <!-- start: Content -->

    <div>
        <hr>
        <ul class="breadcrumb">
            <li><a href="dashboard">Dashboard</a></li>
            <li><a href="test-emails">Test Emails</a></li>
        </ul>
        <hr>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="box">
                <div class="box-header" data-original-title>
                    <h2><i class="fa fa-user"></i><span class="break"></span>Test Emails</h2>
                    <div class="box-icon">
                        <a href="table.html#" class="btn-minimize"><i class="fa fa-chevron-up"></i></a>
                        <a href="table.html#" class="btn-close"><i class="fa fa-times"></i></a>
                    </div>
                </div>
                <div class="box-content">
                    <form>
                        <div class="form-group">
                            <label for="exampleInputEmail1">To Email address</label>
                            <input type="to_email" class="form-control" id="exampleInputEmail1" placeholder="To Email">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">From Email address</label>
                            <input type="from_email" class="form-control" id="exampleInputEmail1" placeholder="From Email">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">To Name</label>
                            <input type="to_name" class="form-control" id="exampleInputEmail1" value="Test Man">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">From Name</label>
                            <input type="from_name" class="form-control" id="exampleInputEmail1" value="Tester Person">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Subject</label>
                            <input type="subject" class="form-control" id="exampleInputEmail1" placeholder="Subject">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Body</label>
                            <input type="body" class="form-control" id="exampleInputEmail1" placeholder="Body">
                        </div>

                        <button type="submit" class="btn btn-default">Submit</button>
                    </form>
                </div>
            </div>
        </div><!--/col-->

    </div><!--/row-->

</div><!--/content-->

<hr>
<!-- page scripts -->

@stop

