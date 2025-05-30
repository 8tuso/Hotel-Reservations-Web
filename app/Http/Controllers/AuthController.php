<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Auth;
use Dotenv\Exception\ValidationException;
use Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function createUser(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'phone' => 'required|string|max:20',
                'password' => 'required|string|min:8|confirmed',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|string|max:10',
                'nationality' => 'nullable|string|max:100',
                'passport_number' => 'nullable|string|max:50',
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
            ]);

            // Create customer record
            $customer = Customer::create([
                'full_name'       => $validatedData['name'],
                'email'           => $validatedData['email'],
                'phone'           => $validatedData['phone'] ?? null,
                'date_of_birth'   => $validatedData['date_of_birth'],
                'gender'          => $validatedData['gender'] ?? null,
                'nationality'     => $validatedData['nationality'] ?? null,
                'passport_number' => $validatedData['passport_number'] ?? null,
                'address'         => $validatedData['address'] ?? null,
                'city'            => $validatedData['city'] ?? null,
                'country'         => $validatedData['country'] ?? null,
                'postal_code'     => $validatedData['postal_code'] ?? null,
            ]);

            // Create user record
            $user = User::create([
                'customer_id' => $customer->id,
                'email'       => $validatedData['email'],
                'name' => $validatedData['name'],
                'password'    => Hash::make($validatedData['password']),
            ]);

            // Authenticate the user immediately
            Auth::login($user);

            // Return JSON success response
            return response()->json([
                'message' => 'User registered and logged in successfully.',
                'user' => $user->only('id', 'email'),
            ], 201); // Use 201 Created status for successful resource creation

        } catch (ValidationException $e) {
            // Laravel's default handler for ValidationException already returns JSON errors
            // but if you wanted custom handling, you'd do it here.
            // For now, let Laravel handle it, as your JS expects the default structure.
            throw $e; // Re-throw to let Laravel's default JSON response handle it
        } catch (\Exception $e) {
            // Catch any other unexpected errors during the process
            return response()->json([
                'message' => 'An error occurred during registration.',
                'error' => $e->getMessage() // Good for debugging, might remove in production
            ], 500); // Internal Server Error
        }
    }

    public function loginUser(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'email' => 'required|email|max:255', // Removed 'unique' rule
                'password' => 'required|string|min:8',
            ]);

            // Attempt to authenticate the user
            if (Auth::attempt($validatedData)) {
                // Authentication successful
                $user = Auth::user(); // Get the authenticated user

                // Return JSON success response
                return response()->json([
                    'message' => 'User logged in successfully.',
                    'user' => $user->only('id', 'email'),
                ], 200); // Use 200 OK status for successful login
            } else {
                // Authentication failed
                return response()->json([
                    'message' => 'Invalid credentials.',
                ], 401); // Use 401 Unauthorized status for invalid credentials
            }

        } catch (ValidationException $e) {
            // Laravel's default handler for ValidationException already returns JSON errors
            // but if you wanted custom handling, you'd do it here.
            // For now, let Laravel handle it, as your JS expects the default structure.
            throw $e; // Re-throw to let Laravel's default JSON response handle it
        } catch (\Exception $e) {
            // Catch any other unexpected errors during the process
            return response()->json([
                'message' => 'An error occurred during login.',
                'error' => $e->getMessage() // Good for debugging, might remove in production
            ], 500); // Internal Server Error
        }
    }


    public function logoutUser()
    {
        Auth::logout(); // logs the user out
        return redirect('/');
    }

}
