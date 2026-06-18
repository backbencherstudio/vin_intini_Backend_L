<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Group;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('admin.universities.index');
        }
        return view('admin.login');
    }

    public function adminlogin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::guard('web')->attempt($credentials)) {

            $user = Auth::guard('web')->user();

            if ($user->email === 'admin@gmail.com') {
                $request->session()->regenerate();
                return redirect()->intended(route('admin.universities.index'));
            }

            Auth::guard('web')->logout();
            return back()->withErrors(['email' => 'You do not have permission to access the admin panel.']);
        }

        return back()->withErrors(['email' => 'The provided credentials do not match our records.']);
    }

    public function adminLogout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }


    //user management functions
    public function allUsers()
    {
        $users = User::all();
        return view('admin.user.index', compact('users'));
    }

    public function userUpdate(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $request->id,
        ]);

        $user = User::findOrFail($request->id);
        $user->update([
            'first_name' => $request->fname,
            'last_name' => $request->lname,
            'email' => $request->email,
        ]);

        return redirect()->back()->with('success', 'User updated successfully');
    }

    public function userDestroy($id)
    {
        User::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'User deleted successfully');
    }


    public function groupIndex()
    {
        $groups = Group::all();
        return view('admin.group.index', compact('groups'));
    }

    public function groupUpdate(Request $request)
    {
        $group = Group::findOrFail($request->id);

        $request->validate([
            'name' => 'required|string|max:255',
            'industry' => 'nullable|array|max:3',
            'logo' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ]);

        $data = $request->all();

        // Handle Checkboxes (Boolean)
        $data['allow_member_invites'] = $request->has('allow_member_invites') ? 1 : 0;
        $data['require_post_approval'] = $request->has('require_post_approval') ? 1 : 0;

        // Image Upload
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }
        if ($request->hasFile('cover_photo')) {
            $data['cover_photo'] = $request->file('cover_photo')->store('covers', 'public');
        }

        $group->update($data);

        return redirect()->back()->with('success', 'Group updated successfully!');
    }

    public function groupDestroy($id)
    {
        Group::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Group deleted successfully!');
    }

    public function postIndex()
    {
        $posts = Post::with('user')->latest()->get();
        return view('admin.post.index', compact('posts'));
    }

    public function postUpdate(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'description' => 'nullable|string',
            'visibility' => 'required|in:public,connections,groups',
            'who_can_comment' => 'required|in:anyone,connections,no_one',
        ]);

        $post = Post::findOrFail($request->id);
        $post->update([
            'description' => $request->description,
            'visibility' => $request->visibility,
            'who_can_comment' => $request->who_can_comment,
        ]);

        return redirect()->back()->with('success', 'Post updated successfully!');
    }

    public function postDestroy($id)
    {
        Post::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Post deleted successfully!');
    }
}
