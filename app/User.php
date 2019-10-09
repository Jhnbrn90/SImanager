<?php

namespace App;

use App\Events\UserWasCreated;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

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
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function compounds()
    {
        return $this->hasMany(Compound::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function addSupervisor(self $user)
    {
        DB::table('student_supervisor')->insert([
            'student_id'    => $this->id,
            'supervisor_id' => $user->id,
        ]);
    }

    public function supervisors()
    {
        return $this->belongsToMany(self::class, 'student_supervisor', 'student_id', 'supervisor_id');
    }

    public function students()
    {
        return $this->belongsToMany(self::class, 'student_supervisor', 'supervisor_id', 'student_id');
    }

    public function isAdmin()
    {
        return in_array($this->email, config('app.admins'));
    }
}
