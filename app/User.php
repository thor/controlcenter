<?php

namespace App;

use App\Exceptions\MissingHandoverObjectException;
use App\Exceptions\PolicyMethodMissingException;
use App\Exceptions\PolicyMissingException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{

    use Notifiable;

    public $timestamps = false;
    protected $dates = [
        'last_login',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'id', 'country', 'group', 'last_login'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token'
    ];

    /**
     * Link to handover data
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @throws MissingHandoverObjectException
     */
    public function handover()
    {
        $handover = $this->hasOne(Handover::class, 'id');

        if ($handover->first() == null) {
            throw new MissingHandoverObjectException($this->id);
        }

        return $handover;
    }

    /**
     * Link user's endorsement
     *
     * @return \App\Solo
     */
    public function soloEndorsement()
    {
        return $this->hasOne(Solo::class);
    }

    public function trainings()
    {
        return $this->hasMany(Training::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function teaches()
    {
        return $this->belongsToMany(Training::class)->withPivot('expire_at');
    }

    public function ratings()
    {
        return $this->belongsToMany(Rating::class);
    }

    public function settings()
    {
        return $this->hasMany(UserSetting::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function vatbooks()
    {
        return $this->hasMany(Vatbook::class);
    }

    public function mentor_countries()
    {
        return $this->belongsToMany(Country::class, 'mentor_country')->withTimestamps();
    }

    public function vote(){
        return $this->hasMany(Vote::class);
    }

    // Get properties from Handover, the variable names here break with the convention.
    public function getLastNameAttribute()
    {
        return $this->handover->last_name;
    }

    public function getFirstNameAttribute()
    {
        return $this->handover->first_name;
    }

    public function getNameAttribute()
    {
        return $this->first_name . " " . $this->last_name;
    }

    public function getEmailAttribute()
    {
        return $this->handover->email;
    }

    public function getRatingAttribute()
    {
        return $this->handover->rating;
    }

    public function getRatingShortAttribute()
    {
        return $this->handover->rating_short;
    }

    public function getRatingLongAttribute()
    {
        return $this->handover->rating_long;
    }

    public function getDivisionAttribute(){
        return $this->handover->division;
    }

    public function getSubdivisionAttribute(){
        return $this->handover->subdivision;
    }

    public function getCountryAttribute(){
        return $this->handover->country;
    }

    public function getVisitingControllerAttribute(){
        return $this->handover->visiting_controller;
    }

    public function getActiveAttribute(){
        return $this->handover->atc_active;
    }

    /**
     * Get the models allowed for the user to be viewed.
     *
     * @param $class
     * @param array $options
     * @return mixed
     * @throws PolicyMethodMissingException
     * @throws PolicyMissingException
     */
    public function viewableModels($class, array $options = [])
    {

        if (policy($class) == null) {
            throw new PolicyMissingException();
        }

        if (!method_exists(policy($class), 'view')) {
            throw new PolicyMethodMissingException('The view method does not exist on the policy.');
        }

        $models = $class::where($options)->get();

        foreach ($models as $key => $model) {
            if ($this->cannot('view', $model)) {
                $models->pull($key);
            }
        }

        return $models;

    }

    // User group checks
    public function isMentor(Country $country = null)
    {

        if ($country == null) {
            return $this->group <= 3 && isset($this->group);
        }

        return $this->group <= 3 &&
            isset($this->group) &&
            $country->mentors->contains($this);

    }

    public function isModerator()
    {
        return $this->group <= 2 && isset($this->group);
    }

    public function isAdmin()
    {
        return $this->group <= 1 && isset($this->group);
    }
}
