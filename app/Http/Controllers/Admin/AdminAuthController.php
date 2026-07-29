<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Education;
use App\Models\User;
use App\Models\Group;
use App\Models\Post;
use App\Models\Institution;
use App\Models\Skill;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('admin.user.management');
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
                return redirect()->intended(route('admin.user.management'));
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

    public function userManagement(Request $request)
    {
        $baseQuery = User::where('is_verified', true)
            ->where('email', '!=', 'admin@gmail.com')
            ->has('profile');

        $totalUsers = (clone $baseQuery)->count();
        $todayUsers = (clone $baseQuery)->whereDate('created_at', now()->today())->count();
        $currentMonthUsers = (clone $baseQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $previousMonthUsers = (clone $baseQuery)->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->count();

        // Filtering & Searching Logic
        $userQuery = (clone $baseQuery)->with('profile');

        if ($request->filled('search')) {
            $search = $request->search;
            $userQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $userQuery->whereBetween('created_at', [$request->from_date . ' 00:00:00', $request->to_date . ' 23:59:59']);
        }

        $filterCount = (clone $userQuery)->count();
        $users = $userQuery->latest()->paginate(10);

        return view('admin.user_management.index', compact('totalUsers', 'todayUsers', 'currentMonthUsers', 'previousMonthUsers', 'users', 'filterCount'));
    }


    //user management functions
    // public function allUsers()
    // {
    //     $users = User::all();
    //     return view('admin.user.index', compact('users'));
    // }

    // public function userUpdate(Request $request)
    // {
    //     $request->validate([
    //         'id' => 'required',
    //         'fname' => 'required|string|max:255',
    //         'lname' => 'required|string|max:255',
    //         'email' => 'required|email|unique:users,email,' . $request->id,
    //     ]);

    //     $user = User::findOrFail($request->id);
    //     $user->update([
    //         'first_name' => $request->fname,
    //         'last_name' => $request->lname,
    //         'email' => $request->email,
    //     ]);

    //     return redirect()->back()->with('success', 'User updated successfully');
    // }

    public function allUsers()
    {
        $users = User::with('education.institution')->get();

        $institutions = Institution::all();

        return view('admin.user.index', compact('users', 'institutions'));
    }

    public function userUpdate(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $request->id,
            'institution_id' => 'required|exists:institutions,id',
            'degree' => 'required|string|max:255',
            'field_study' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($request->id);

        // $user->update([
        //     'first_name' => $request->fname,
        //     'last_name' => $request->lname,
        //     'email' => $request->email,
        // ]);

        Education::updateOrCreate(
            ['user_id' => $user->id],
            [
                'institution_id' => $request->institution_id,
                // 'degree' => $request->degree,
                // 'field_study' => $request->field_study,
            ]
        );

        return redirect()->back()->with('success', 'User and Education updated successfully');
    }

    // public function userDestroy($id)
    // {
    //     User::findOrFail($id)->delete();
    //     return redirect()->back()->with('success', 'User deleted successfully');
    // }


    // public function groupIndex()
    // {
    //     $groups = Group::all();
    //     return view('admin.group.index', compact('groups'));
    // }

    // public function groupUpdate(Request $request)
    // {
    //     $group = Group::findOrFail($request->id);

    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'industry' => 'nullable|array|max:3',
    //         'logo' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
    //         'cover_photo' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
    //     ]);

    //     $data = $request->all();

    //     // Handle Checkboxes (Boolean)
    //     $data['allow_member_invites'] = $request->has('allow_member_invites') ? 1 : 0;
    //     $data['require_post_approval'] = $request->has('require_post_approval') ? 1 : 0;

    //     // Image Upload
    //     if ($request->hasFile('logo')) {
    //         $data['logo'] = $request->file('logo')->store('logos', 'public');
    //     }
    //     if ($request->hasFile('cover_photo')) {
    //         $data['cover_photo'] = $request->file('cover_photo')->store('covers', 'public');
    //     }

    //     $group->update($data);

    //     return redirect()->back()->with('success', 'Group updated successfully!');
    // }

    // public function groupDestroy($id)
    // {
    //     Group::findOrFail($id)->delete();
    //     return redirect()->back()->with('success', 'Group deleted successfully!');
    // }

    // public function postIndex()
    // {
    //     $posts = Post::with('user')->latest()->get();
    //     return view('admin.post.index', compact('posts'));
    // }

    // public function postUpdate(Request $request)
    // {
    //     $request->validate([
    //         'id' => 'required',
    //         'description' => 'nullable|string',
    //         'visibility' => 'required|in:public,connections,groups',
    //         'who_can_comment' => 'required|in:anyone,connections,no_one',
    //     ]);

    //     $post = Post::findOrFail($request->id);
    //     $post->update([
    //         'description' => $request->description,
    //         'visibility' => $request->visibility,
    //         'who_can_comment' => $request->who_can_comment,
    //     ]);

    //     return redirect()->back()->with('success', 'Post updated successfully!');
    // }

    // public function postDestroy($id)
    // {
    //     Post::findOrFail($id)->delete();
    //     return redirect()->back()->with('success', 'Post deleted successfully!');
    // }

    // public function institutionIndex()
    // {
    //     $institutions = Institution::latest()->paginate(20);
    //     return view('admin.institutions.index', compact('institutions'));
    // }

    // public function institutionUpdate(Request $request, Institution $institution)
    // {
    //     $request->validate([
    //         'name' => 'required|string|max:255|unique:institutions,name,' . $institution->id,
    //         'type' => 'nullable|string|max:255',
    //         'state' => 'nullable|string|max:255',
    //         'country' => 'nullable|string|max:255',
    //         'website' => 'nullable|url|max:255',
    //     ]);

    //     $institution->update($request->all());

    //     return redirect()->back()->with('success', 'Institution updated successfully!');
    // }

    // public function skillIndex()
    // {
    //     $skills = Skill::latest()->paginate(10);
    //     return view('admin.skill.index', compact('skills'));
    // }

    // public function skillUpdate(Request $request, Skill $skill)
    // {
    //     $request->validate([
    //         'name' => 'required|string|max:255|unique:skills,name,' . $skill->id,
    //     ]);

    //     $skill->update($request->all());

    //     return redirect()->back()->with('success', 'Skill updated successfully!');
    // }

    // public function skillDestroy(Skill $skill)
    // {
    //     $skill->delete();
    //     return redirect()->back()->with('success', 'Skill deleted successfully!');
    // }
}
