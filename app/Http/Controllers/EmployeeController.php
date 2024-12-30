<?php

namespace App\Http\Controllers;



use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;
class EmployeeController extends Controller
{


    // public function __construct()
    // {

    //     $this->middleware('permission:addemployee')->only('register');
    // }


    public function getAllusers()
    {
        $user = auth()->user();


        if (!$user->hasRole('owner')) {
            return response()->json(['message' => 'Permission denied: Only owners'], 403);
        }

        $user = user::with('teams','department','salaries')->get();
        return response()->json($user);
    }






    public function getAllRoles()
    {
        $user = auth()->user();


        if (!$user->hasRole('owner')) {
            return response()->json(['message' => 'Permission denied: Only owners'], 403);
        }

        $roles = Role::all();
        return response()->json($roles);
    }


    public function getAllPermissions()
    {
        $user = auth()->user();


        if (!$user->hasRole('owner')) {
            return response()->json(['message' => 'Permission denied: Only owners'], 403);
        }

        $roles = Permission::all();
        return response()->json($roles);
    }


    public function register(Request $request)
    {
        $user = auth()->user();

        // // Check for the required permissions
        if (!$user->hasRole('owner') && !$user->can('addemployee')) {
            return response()->json(['message' => 'Permission denied: Only owners or users with the addemployee permission can create users.'], 403);
        }


        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'required|string',
            'role' => 'required|string|exists:roles,name',
            'national_id'=>'required|string|unique:users,national_id',
            'birth_date'=>'required|date',
            'team_id' => 'required|integer|exists:teams,id',
            'department_id' => 'required|integer|exists:departments,id',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);


        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'National_id' =>$request->national_id,
            'birth_date' =>$request->birth_date,
            'team_id' => $request->team_id,
            'department_id' => $request->department_id,
        ]);


        $user->assignRole($request->role);

        if ($request->permissions) {
        $user->syncPermissions($request->permissions);
        }
        return response()->json(['message' => 'User registered successfully', 'user' => $user], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $role = $user->getRoleNames();
        $permissions = $user->getAllPermissions(); // This will return a collection of permissions

        return response()->json(['message' => 'Login successful',
          'token' => $token,
           'role' => $role,
           'permissions' => $permissions,


        ], 200);
    }

    public function updateUser(Request $request, $id)
{
    $user = auth()->user();

    // Permission check
    // if (!$user->hasRole('owner') && !$user->can('updateuserinfo')) {
    //     return response()->json(['message' => 'Permission denied: Only owners or users with the updateuserinfo permission can update user info.'], 403);
    // }

    // Fetch the user by ID
    $user = User::find($id);

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    // Validate input
    $request->validate([
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|email|unique:users,email,' . $user->id,
        'password' => 'sometimes|string|min:6',
        'role' => 'sometimes|string|exists:roles,name',
        'team_id' => 'sometimes|integer|exists:teams,id',
        'department_id' => 'sometimes|integer|exists:departments,id',
        'permissions' => 'sometimes|array',
        'permissions.*' => 'string|exists:permissions,name',
    ]);


    $user->update($request->only([
        'name',
        'email',
        'password',
        'team_id',
        'department_id',
    ]));
    $user->save();

    // Update role and permissions
    if ($request->has('role')) {
        $user->syncRoles([$request->role]);
        $role = Role::findByName($request->role);

        if ($role) {
            $user->syncPermissions($role->permissions);
        }
    }

    // Sync provided permissions if sent
    if ($request->has('permissions')) {
        $user->syncPermissions($request->permissions);
    }

    return response()->json(['message' => 'User updated successfully', 'user' => $user], 200);
}

    public function deleteUser($id)
    {

        $currentuser = auth()->user();
        if (!$currentuser->hasRole('owner') ) {
            return response()->json(['message' => 'Permission denied: Only owners or users with the delete_user permission can update user info.'], 403);
        }
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }


        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);

    }

    public function updatePassword(Request $request,$id)
    {

        $currentuser = auth()->user();
        if (!$currentuser->hasRole('owner') ) {
            return response()->json(['message' => 'Permission denied: Only owners or users with the update_Password permission can update user info.'], 403);
        }
        $request->validate([
            'password' => 'required|string|min:6',
        ]);
        $user = User::find($id);

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    $user->password = Hash::make($request->password);
    $user->save();

    return response()->json(['message' => 'Password updated successfully'], 200);
}



public function addRole(Request $request)
{
    $currentUser = auth()->user();

    // Permission check
    if (!$currentUser->hasRole('owner') && !$currentUser->can('addrole')) {
        return response()->json(['message' => 'Permission denied: Only owners or users with the addrole permission can create roles.'], 403);
    }

    // Validate incoming request
    $request->validate([
        'name' => 'required|string|unique:roles,name|max:255',
    ]);

    try {
        // Create the role
        $role = Role::create(['name' => $request->name]);

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role,
        ], 201);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Error creating role', 'error' => $e->getMessage()], 500);
    }
}

public function index()
{
    $currentUser = auth()->user();

    if (!$currentUser->hasRole('owner') ) {
        return response()->json(['message' => 'Permission denied: Only owners with the view_users.'], 403);
    }


    $users = User::role('teamleader')->get();

    return response()->json($users);
}


    /**
     * Soft delete a user by ID.
     */
    public function softDeleteUser($id)
    {

        $user = auth()->user();

        if (!$user->hasRole('owner') ) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User soft deleted successfully'], 200);
    }

    /**
     * Restore a soft-deleted user by ID.
     */
    public function restoreUser($id)
    {

        $user = auth()->user();


        if (!$user->hasRole('owner') ) {
            return response()->json(['message' => 'Permission denied'], 403);
        }


        $user = User::onlyTrashed()->find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found or not deleted'], 404);
        }

        $user->restore();

        return response()->json(['message' => 'User restored successfully'], 200);
    }

    /**
     * Retrieve all users ordered by birth month.
     */

   

     public function getUsersByBirthMonth()
     {
         $user = auth()->user();

         if (!$user->hasRole('owner')) {
             return response()->json(['message' => 'Permission denied'], 403);
         }

         // Get today's date
         $today = Carbon::today();

         // Get users with birth month and day that match today's month and a day range (e.g., +/- 7 days)
         $users = User::select('id', 'name', 'birth_date')
             ->whereMonth('birth_date', $today->month)  // Filter by current month
             ->whereBetween(
                 'birth_date',
                 [
                     $today->copy()->subDays(7)->format('Y-m-d'), // 7 days before today
                     $today->copy()->addDays(7)->format('Y-m-d')  // 7 days after today
                 ]
             )
             ->get() // Fetch the results from the database
             ->map(function($user) use ($today) {
                 // Calculate the number of days between the user's birthday and today
                 $user->days_until_birthday = $this->calculateDaysUntilBirthday($user->birth_date, $today);
                 return $user;
             })
             ->sortBy('days_until_birthday')  // Sort the collection by days until the birthday
             ->values();  // Reindex the array to get a clean array format

         return response()->json($users, 200);
     }

     // Helper method to calculate days until next birthday
     private function calculateDaysUntilBirthday($birthDate, $today)
     {
         // Create a Carbon instance for this user's birthday in the current year
         $birthdayThisYear = Carbon::parse($birthDate)->year($today->year);

         // If the birthday has already passed this year, use the next year
         if ($birthdayThisYear->isPast()) {
             $birthdayThisYear->addYear();
         }

         // Return the difference in days between today and the user's next birthday
         return $birthdayThisYear->diffInDays($today, false);  // Use `false` to allow negative difference for past birthdays
     }

}



