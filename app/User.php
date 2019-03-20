<?php

namespace App;

use App\Bundle;
use App\Project;
use App\Events\UserWasCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
    * The event map for the model.
    *
    * @var array 
    */
    protected $dispatchesEvents = [
        'created'   => UserWasCreated::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'prefix',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function bundles()
    {
        return $this->hasMany(Bundle::class)->latest();
    }

    public function projects()
    {
        return $this->hasManyThrough('App\Project', 'App\Bundle')->latest();
    }
    
    public function reactions()
    {
        return $this->projects->flatMap(function ($project) {
            return $project->reactions;
        });
    }

    public function getNewReactionLabelAttribute()
    {
        $experimentNumber = $this->reactions()->count() + 1;
        return "{$this->prefix}_{$experimentNumber}";
    }

    public function addSupervisor(User $user)
    {
        $this->supervisors()->attach($user);
    }

    public function supervisors()
    {
        return $this->belongsToMany(User::class, 'student_supervisor', 'student_id', 'supervisor_id');
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'student_supervisor', 'supervisor_id', 'student_id');
    }

    public function isAdmin()
    {
        return in_array($this->email, config('app.admins'));
    }

    public function setImpersonating($id)
    {
        Session::put('impersonator', auth()->id());
        Session::put('impersonate', $id);
    }

    public function stopImpersonating()
    {
        Session::forget('impersonator');
        Session::forget('impersonate');
    }

    public function isImpersonating()
    {
        return Session::has('impersonate') && Session::has('impersonator');
    }
}
