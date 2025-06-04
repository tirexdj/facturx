<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuotePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any quotes.
     */
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    /**
     * Determine whether the user can view the quote.
     */
    public function view(User $user, Quote $quote): bool
    {
        return $user->company_id === $quote->company_id;
    }

    /**
     * Determine whether the user can create quotes.
     */
    public function create(User $user): bool
    {
        return $user->company_id !== null;
    }

    /**
     * Determine whether the user can update the quote.
     */
    public function update(User $user, Quote $quote): bool
    {
        return $user->company_id === $quote->company_id;
    }

    /**
     * Determine whether the user can delete the quote.
     */
    public function delete(User $user, Quote $quote): bool
    {
        return $user->company_id === $quote->company_id;
    }

    /**
     * Determine whether the user can restore the quote.
     */
    public function restore(User $user, Quote $quote): bool
    {
        return $user->company_id === $quote->company_id;
    }

    /**
     * Determine whether the user can permanently delete the quote.
     */
    public function forceDelete(User $user, Quote $quote): bool
    {
        return $user->company_id === $quote->company_id;
    }

    /**
     * Determine whether the user can send the quote.
     */
    public function send(User $user, Quote $quote): bool
    {
        return $user->company_id === $quote->company_id;
    }

    /**
     * Determine whether the user can convert the quote to invoice.
     */
    public function convert(User $user, Quote $quote): bool
    {
        return $user->company_id === $quote->company_id && $quote->status === 'accepted';
    }

    /**
     * Determine whether the user can duplicate the quote.
     */
    public function duplicate(User $user, Quote $quote): bool
    {
        return $user->company_id === $quote->company_id;
    }
}
