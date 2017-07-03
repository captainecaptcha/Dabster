<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\User;
use App\UserLike;
use App\UserPost;
use JWTAuth;
use JWTFactory;
use Session;
use Redirect;
use Image;
use Storage;
use Tymon\JWTAuthExceptions\JWTException;
use Illuminate\Support\Facades\Hash;
use DB;

class UsersController extends Controller
{
    public function getUsers()
    {
        return User::all();
    }

    // Creates a user and generates a token
    public function register(Request $request)
    {
        $newUser = new User;

        $newUser->pseudo = $request->pseudo;
        $newUser->email = $request->email;
        $newUser->password = bcrypt($request->password);
        $newUser->pp = '/img/default.png';
        $newUser->description = "";

        $newUser->save();

        try
        {
            $token = JWTAuth::fromUser($newUser);
            Session::put('user_id', $newUser->id);
            Session::put('token', $token);
            return redirect('/');
            //return response()->json(compact('token'));
        }
        catch (JWTException $e)
        {
            return view('register'); 
            //response()->json(['error' => 'could_not_create_token'], 500);
        }
    }

    // Check user's credentials and generates a token
    public function authenticate(Request $request)
    {
        $user = User::where('pseudo', $request->pseudo)->first();
        $errors = [];
        try
        {
            if (empty($user) || !Hash::check($request->password, $user->password))
            {
                array_push($errors, "Mauvais pseudo/mot de passe, veuillez réessayer");
                return view('login', compact("errors"));
            }
            $token = JWTAuth::fromUser($user);
            Session::put('user_id', $user->id);
            Session::put('token', $token);
            return redirect('/');
        }
        catch (JWTException $e)
        {   
            array_push($errors, "Erreur de token");
            return view('login', compact("errors"));
        }
    }

    public function authenticateAPI(Request $request)
    {
        $user = User::where('pseudo', $request->pseudo)->first();
        $errors = [];
        try
        {
            if (empty($user) || !Hash::check($request->password, $user->password))
            {
                array_push($errors, "Mauvais pseudo/mot de passe, veuillez réessayer");
                return view('login', compact("errors"));
            }
            $token = JWTAuth::fromUser($user);
            return $token;
        }
        catch (JWTException $e)
        {
            array_push($errors, "Erreur de token");
            return view('login', compact("errors"));
        }
    }

    public function logout() {
        Session::remove('token');
        Session::remove('user_id');
        return redirect('/');
    }

    private function GetUser($userId)
    {
        return User::where('id', $userId)->first();
    }

    private function GetAuthUser()
    {
        return JWTAuth::setToken(Session::get("token"))->authenticate();
    }

    public function profilePosts(Request $request, $userId) {
        $user = $this->GetUser($userId);
        $authUser = $this->GetAuthUser();

        $followers = $user->usersFollowers;
        $alreadyFollows = false;
        foreach ($followers as &$follower) {
            if ($follower->id == $authUser->id) {
                $alreadyFollows = true;
            }
        }
        $followingsCount = $user->usersFollowings->count();
        $followersCount = $user->usersFollowers->count();
        $likesCount = $user->likes->count();
        $content = $user->posts()->with('user')->paginate(4);
        $page = 'posts';

        return view('profile.posts',
            compact('user', 'alreadyFollows', 'followingsCount', 'page',
                'followersCount', 'likesCount', 'page', 'content'));
    }

    public function profileLikes(Request $request, $userId) {
        $user = $this->GetUser($userId);
        $authUser = $this->GetAuthUser();

        $followers = $user->usersFollowers;
        $alreadyFollows = false;
        foreach ($followers as &$follower) {
            if ($follower->id == $authUser->id) {
                $alreadyFollows = true;
            }
        }
        $followingsCount = $user->usersFollowings->count();
        $followersCount = $user->usersFollowers->count();
        $likesCount = $user->likes->count();
        $content = UserLike::where('user_id', $user->id)->with('user_posts')->paginate(4);
        $page = 'likes';

        return view('profile.likes',
            compact('user', 'alreadyFollows', 'followingsCount', 'page',
                'followersCount', 'likesCount', 'page', 'content'));
    }

    public function profileFollowings(Request $request, $userId) {
        $user = $this->GetUser($userId);
        $authUser = $this->GetAuthUser();

        $followers = $user->usersFollowers;
        $alreadyFollows = false;
        foreach ($followers as &$follower) {
            if ($follower->id == $authUser->id) {
                $alreadyFollows = true;
            }
        }
        $followingsCount = $user->usersFollowings->count();
        $followersCount = $user->usersFollowers->count();
        $likesCount = $user->likes->count();
        $content = $user->usersFollowings()->paginate(4);
        $page = 'followings';

        return view('profile.followings',
            compact('user', 'alreadyFollows', 'followingsCount', 'page',
                'followersCount', 'likesCount', 'page', 'content'));
    }

    public function profileFollowers(Request $request, $userId) {
        $user = $this->GetUser($userId);
        $authUser = $this->GetAuthUser();

        $followers = $user->usersFollowers;
        $alreadyFollows = false;

        foreach ($followers as $follower)
            if ($follower->id == $authUser->id)
                $alreadyFollows = true;

        $followingsCount = $user->usersFollowings->count();
        $followersCount = $user->usersFollowers->count();
        $likesCount = $user->likes->count();
        $content = $user->usersFollowers()->paginate(4);
        $page = 'followers';

        return view('profile.following',
            compact('user', 'alreadyFollows', 'followingsCount', 'page',
                'followersCount', 'likesCount', 'page', 'content'));
    }

    public function profileEdit(Request $request, $userId) {
        $user = $this->GetUser($userId);
        return view('profile.edit', compact('user'));
    }

    public function updateProfile(Request $request, $userId) {
        $user = $this->GetUser($userId);
        $user->description = $request->description;

        if ($request->pp)
        {
            $file = $request->pp;
            $path = $file->storeAs('pp', $userId.'.jpg');
            print_r($path);
            $image = Image::make(Storage::disk('local')->get($path))->resize(256, 256)->stream();
            Storage::disk('local')->put($path, $image);
            $user->pp = '/img/pp/'.$userId.'.jpg';
        }
        $user->save();

        return redirect('/users/' . $userId);
    }

    public function feed($userId)
    {
        $user = $this->GetUser($userId);
        $followings = array();
        $usersFollowings = $user->usersFollowings;
        foreach ($usersFollowings as $following)
            array_push($followings, $following->id);

        $posts = UserPost::whereIn('user_id', $followings)->with('user')->orderByDesc('post_date')->paginate(4);
        $page = "feed";

        return view('home', compact('posts', 'page', 'user'));
    }

    public function trending($userId)
    {
        $user = $this->GetUser($userId);

        $posts = UserLike::select('user_post_id', DB::raw('count(*) as total'))
            ->groupBy('user_post_id')
            ->orderByDesc('total')
            ->with('user_posts')
            ->paginate(4);
        $page = "trending";

        return view('home', compact('posts', 'page', 'user'));
    }

    public function recent($userId)
    {
        $user = $this->GetUser($userId);

        $posts = UserPost::orderByDesc('post_date')->paginate(4);
        $page = "recent";

        return view('home', compact('posts', 'page', 'user'));
    }
}
