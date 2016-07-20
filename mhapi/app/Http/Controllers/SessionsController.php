<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redirect;
//use Laracasts\Flash\Flash;
use App\Forms\SignInForm;
use App\Models\ser;
use Illuminate\Http\Request;
use View;
use DB;
use Input;
use Auth;

class SessionsController extends Controller
{

    function __construct(SignInForm $signInForm)
    {

//        $this->beforeFilter('guest',['except' => 'destroy']);

        $this->signInForm = $signInForm;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {




        if(Auth::check())
        {
            return Redirect::intended('dashboard');
        }

            return View::make('sessions.create')->with('currentUser',Auth::user());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return Response
     */
    public function destroy()
    {

        Auth::logout();

//        Flash::success('You have been logged out.');

        return Redirect::intended('login');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {

        $formData = ['email' => $request->get('email'),'password' =>$request->get('password')];

        if(Auth::attempt($formData))
        {

//            Flash::message('Welcome back!');

            return Redirect::intended('dashboard');
        }
        else
        {

//            Flash::error('Your account is not authorized.');

            Auth::logout();

            return Redirect::intended('/login');

        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        //
    }

}
