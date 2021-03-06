<?php

namespace App\Http\Controllers;

use App\UserPost;
use Session;
use JWTAuth;

class PagesController extends Controller
{
    public static function home() {
        try {
            $token = Session::get("token");
            if (!$token)
            {
                $posts = UserPost::orderByDesc('post_date')
                    ->with('user')
                    ->withCount('comments')
                    ->withCount('likes')
                    ->paginate(4);
                $page = "";
                return view('home', compact('posts', 'page'));
            }

            $user = JWTAuth::setToken($token)->authenticate();
            if (!$user)
            {
                Session::flush();
                return redirect('/');
            }

            // feed for connected user
            return redirect('/users/' . $user->id . '/feed');

        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            Session::flush();
            return redirect('/');

        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            Session::flush();
            return redirect('/');

        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
            Session::flush();
            return redirect('/');
        }
    }

    public function login()
    {
        $error = null;
        return view('login', compact('error'));
    }

    public function register()
    {
        $error = null;
        return view('register', compact('error'));
    }

    public function signout()
    {
        $error = null;
        return view('signout', compact('error'));
    }
}