<?php

namespace App\Policies;

use App\Models\TimeEntryProjectPhaseReport;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TimeEntryProjectPhaseReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_time::entry::project::phase::report');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TimeEntryProjectPhaseReport $timeEntryProjectPhaseReport): bool
    {
        return $user->can('view_time::entry::project::phase::report');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_time::entry::project::phase::report');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TimeEntryProjectPhaseReport $timeEntryProjectPhaseReport): bool
    {
        return $user->can('update_time::entry::project::phase::report');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TimeEntryProjectPhaseReport $timeEntryProjectPhaseReport): bool
    {
        return $user->can('delete_time::entry::project::phase::report');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_time::entry::project::phase::report');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, TimeEntryProjectPhaseReport $timeEntryProjectPhaseReport): bool
    {
        return $user->can('force_delete_time::entry::project::phase::report');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_time::entry::project::phase::report');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, TimeEntryProjectPhaseReport $timeEntryProjectPhaseReport): bool
    {
        return $user->can('restore_time::entry::project::phase::report');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_time::entry::project::phase::report');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, TimeEntryProjectPhaseReport $timeEntryProjectPhaseReport): bool
    {
        return $user->can('replicate_time::entry::project::phase::report');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_time::entry::project::phase::report');
    }
}
