<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Complaint;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComplaintPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Complaint $complaint)
    {
        // Passenger can view their own complaints
        if ($user->id === $complaint->user_id) {
            return true;
        }

        // Admin can view all complaints
        if ($user->isAdmin()) {
            return true;
        }

        // Airline staff can view complaints for their airline
        if ($user->isAirlineStaff() && $complaint->airline_id === $user->airlines()->first()->id) {
            return true;
        }

        // Airport staff can view complaints for their airport
        if ($user->isAirportStaff() && $complaint->airport_id === $user->airports()->first()->id) {
            return true;
        }

        return false;
    }

    public function respond(User $user, Complaint $complaint)
    {
        // Only staff or admin can respond to complaints
        return $user->isStaff() || $user->isAdmin();
    }

    public function update(User $user, Complaint $complaint)
    {
        // Admin can update any complaint
        if ($user->isAdmin()) {
            return true;
        }

        // Airline staff can update complaints for their airline
        if ($user->isAirlineStaff() && $complaint->airline_id === $user->airlines()->first()->id) {
            return true;
        }

        // Airport staff can update complaints for their airport
        if ($user->isAirportStaff() && $complaint->airport_id === $user->airports()->first()->id) {
            return true;
        }

        return false;
    }
}
