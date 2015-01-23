<?php namespace Strimoid\Models;

use Auth, Config, DB, Image, Str, Hash;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

/**
 * User model
 *
 * @property string $_id
 * @property string $name User name
 * @property string $email User email address, hashed
 * @property string $password User password, hashed
 * @property DateTime $created_at
 */
class User extends BaseModel implements AuthenticatableContract, CanResetPasswordContract {

    use Authenticatable, CanResetPassword;

    protected $table = 'users';
    protected $visible = [
        'id', 'age', 'avatar', 'created_at',
        'description', 'location', 'sex', 'name',
    ];
    protected $dates = ['last_login'];

    public function getReminderEmail()
    {
        return Str::lower($this->email);
    }

    public function getColoredName()
    {
        $type = $this->type ?: 'normal';
        return '<span class="user_'. $type .'">'. $this->name .'</span>';
    }

    public function getAvatarPath($width = null, $height = null)
    {
        $host = Config::get('app.cdn_host');

        // Show default avatar if user is blocked
        if (Auth::check() && Auth::user()->isBlockingUser($this))
        {
            return $this->getDefaultAvatarPath();
        }

        if ($this->avatar && $width && $height)
        {
            return $host .'/'. $width .'x'. $height .'/avatars/'. $this->avatar;
        }
        elseif ($this->avatar)
        {
            return $host .'/avatars/'. $this->avatar;
        }

        return $this->getDefaultAvatarPath();
    }

    public function getDefaultAvatarPath()
    {
        $host = Config::get('app.cdn_host');
        return $host . '/duck/'. $this->name .'.svg';
    }

    public function getBlockedDomainsAttribute($value)
    {
        $blockedDomains = $this->getAttributeFromArray('_blocked_domains');

        return (array) $blockedDomains;
    }

    public function getSexClass()
    {
        if ($this->sex && in_array($this->sex, ['male', 'female']))
        {
            return $this->sex;
        }

        return 'nosex';
    }

    public function setNameAttribute($value)
    {
        $lowercase = Str::lower($value);

        $this->attributes['name'] = $value;
        $this->attributes['shadow_name'] = $lowercase;
    }

    public function setEmailAttribute($value)
    {
        $lowercase = Str::lower($value);

        $this->attributes['email'] = hash_email($lowercase);

        $shadow = str_replace('.', '', $lowercase);
        $shadow = preg_replace('/\+(.)*@/', '@', $shadow);

        $this->attributes['shadow_email'] = hash_email($shadow);
    }

    public function setNewEmailAttribute($value)
    {
        $lowercase = Str::lower($value);

        $this->attributes['new_email'] = hash_email($lowercase);

        $shadow = str_replace('.', '', $lowercase);
        $shadow = preg_replace('/\+(.)*@/', '@', $shadow);

        $this->attributes['shadow_new_email'] = hash_email($shadow);
    }

    public function changeEmailHashes($email, $shadow)
    {
        $this->attributes['email'] = $email;
        $this->attributes['shadow_email'] = $shadow;
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function contents()
    {
        return $this->hasMany('Strimoid\Models\Content');
    }

    public function comments()
    {
        return $this->hasMany('Strimoid\Models\Comment');
    }

    public function entries()
    {
        return $this->hasMany('Strimoid\Models\Entry');
    }

    public function bannedGroups()
    {
        $groups = DB::table('group_bans')
            ->where('user_id', $this->getKey())
            ->lists('group_id');
        return (array) $groups;
    }

    public function blockedGroups()
    {
        $groups = DB::table('group_blocks')
            ->where('user_id', $this->getKey())
            ->lists('group_id');
        return (array) $groups;
    }

    public function blockedUsers()
    {
        $users = DB::table('user_blocks')
            ->where('user_id', $this->getKey())
            ->lists('target_id');
        return (array) $users;
    }

    public function subscribedGroups()
    {
        $groups = DB::table('group_subscribers')
            ->where('user_id', $this->getKey())
            ->lists('group_id');
        return (array) $groups;
    }

    public function moderatedGroups()
    {
        $groups = DB::table('group_moderators')
            ->where('user_id', $this->getKey())
            ->lists('group_id');
        return (array) $groups;
    }

    public function folders()
    {
        return $this->embedsMany('Strimoid\Models\Folder', '_folders');
    }

    public function setAvatar($file)
    {
        $this->deleteAvatar();

        $filename = Str::random(8) .'.png';

        $img = Image::make($file);
        $img->fit(100, 100);
        $img->save(Config::get('app.uploads_path').'/avatars/'. $filename);

        $this->avatar = $filename;
    }

    public function deleteAvatar()
    {
        if (!$this->avatar) return;

        File::delete(Config::get('app.uploads_path').'/avatars/'. $this->avatar);
        $this->unset('avatar');
    }

    public function isBanned(Group $group)
    {
        $isBanned = GroupBanned::where('group_id', $group->getKey())
            ->where('user_id', $this->getKey())->first();

        return (bool) $isBanned;
    }

    public function isAdmin($group)
    {
        if ($group instanceof Group) $group = $group->_id;

        $isAdmin = GroupModerator::where('group_id', $group)
            ->where('user_id', $this->getKey())
            ->where('type', 'admin')->first();

        return (bool) $isAdmin;
    }

    public function isModerator($group)
    {
        if ($group instanceof Group) $group = $group->_id;

        return in_array($group, $this->moderatedGroups());
    }

    public function isSubscriber(Group $group)
    {
        $isSubscriber = GroupSubscriber::where('group_id', $group->getKey())
            ->where('user_id', $this->getKey())->first();

        return (bool) $isSubscriber;
    }

    public function isBlocking(Group $group)
    {
        $isBlocking = GroupBlock::where('group_id', $group->getKey())
            ->where('user_id', $this->getKey())->first();

        return (bool) $isBlocking;
    }

    public function isObservingUser($user)
    {
        if ($user instanceof User) $user = $user->_id;

        return in_array($user, (array) $this->_observed_users);
    }

    public function isBlockingUser($user)
    {
        if ($user instanceof User) $user = $user->_id;

        if (in_array($user, $this->blockedUsers()))
            return true;
        else
            return false;
    }

    /* Scopes */

    public function scopeShadow($query, $name)
    {
        return $query->where('shadow_name', shadow($name));
    }

}