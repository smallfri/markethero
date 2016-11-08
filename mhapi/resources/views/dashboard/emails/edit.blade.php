@extends('templates.default')

@section('content')

    <div id="content" class="col-sm-10" xmlns="http://www.w3.org/1999/html">
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
                        {!! Form::open(array( 'method'=>'post')) !!}
                        <div class="form-group">
                            <label for="bypass_queue">Bypass Queue:</label>
                            <select name="bypass_queue">
                                <option value="0" selected="selected">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">To Email address</label>
                            <input type="to_email" class="form-control" id="to_email" name="to_email" placeholder="To Email">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">From Email address</label>
                            <input type="from_email" class="form-control" id="from_email" name="from_email" placeholder="From Email">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">To Name</label>
                            <input type="to_name" class="form-control" id="to_name" name="to_name" value="Test Man">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">From Name</label>
                            <input type="from_name" class="form-control" id="from_name" name="from_name" value="Tester Person">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Subject</label>
                            <input type="subject" class="form-control" id="subject" name="subject" placeholder="Subject">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Body</label>
                            <textarea name="body" style="width:100%;height:400px;">

                            </textarea>
                        </div>

                        <button type="submit" class="btn btn-default">Submit</button>
                        </form>
                    </div>
                </div>
            </div><!--/col-->

        </div><!--/row-->
        <div class="row">
            Response:</br>
            {!! $r !!}
        </div>

    </div><!--/content-->

    <hr>
    <!-- page scripts -->

@stop

