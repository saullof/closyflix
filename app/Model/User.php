<?php

namespace App;

use App\Model\Post;
use App\Model\Subscription;
use App\Model\UserList;
use App\Model\Coupon;
use App\Providers\GenericHelperServiceProvider;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends \TCG\Voyager\Models\User implements MustVerifyEmail
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'role_id', 'password', 'username', 'bio', 'birthdate', 'location', 'website', 'avatar', 'cover', 'postcode', 'settings',
        'billing_address', 'first_name', 'last_name', 'profile_access_price',
        'gender_id', 'gender_pronoun',
        'profile_access_price_6_months',
        'profile_access_price_12_months',
        'profile_access_price_3_months',
        'public_profile', 'city', 'country', 'state', 'email_verified_at', 'paid_profile',
        'auth_provider','auth_provider_id', 'enable_2fa', 'enable_geoblocking', 'open_profile', 'referral_code', 'phone'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'public_profile' => 'boolean',
        'settings' => 'array',
    ];

    public function coupon()
    {
        return $this->hasMany(Coupon::class);
    }
    /*
     * Virtual attributes
     */
    public function getAvatarAttribute($value)
    {
        return GenericHelperServiceProvider::getStorageAvatarPath($value);
    }

    public function getCoverAttribute($value)
    {
        return GenericHelperServiceProvider::getStorageCoverPath($value);
    }


    /**
     * Gets current count of active subscribers
     * @return int
     * @throws \Exception
     */

    public function getFansCountAttribute(){
        $activeSubscriptionsCount = Subscription::query()
            ->where('recipient_user_id', Auth::user()->id)
            ->whereDate('expires_at', '>=', new \DateTime('now', new \DateTimeZone('UTC')))
            ->count('id');

        return $activeSubscriptionsCount;
    }

    /**
     * Gets the count of followers
     * @return int|mixed
     */
    public function getFollowingCountAttribute(){
        $userId = Auth::user()->id;
        $userFollowingMembers = UserList::query()
            ->where(['user_id' => $userId, 'type' => 'following'])
            ->withCount('members')->first();

        return $userFollowingMembers != null && $userFollowingMembers->members_count > 0 ? $userFollowingMembers->members_count : 0;
    }


    public function getIsActiveCreatorAttribute($value)
    {
        if(getSetting('compliance.monthly_posts_before_inactive')){
            $check = Post::where('user_id', $this->id)->where('created_at','>=',Carbon::now()->subdays(30))->count();
            $hasPassedPreApprovedLimit = true;
            if(getSetting('compliance.admin_approved_posts_limit')){
                $hasPassedPreApprovedLimit = Post::where('user_id', $this->id)->where('status', Post::APPROVED_STATUS)->count();
                $hasPassedPreApprovedLimit = $hasPassedPreApprovedLimit >= (int)getSetting('compliance.admin_approved_posts_limit');
            }
            return ($hasPassedPreApprovedLimit && $check >= (int)getSetting('compliance.monthly_posts_before_inactive'));
        }
        return true;
    }

    /*
     * Relationships
     */
    public function posts()
    {
			if(getSetting('compliance.admin_approved_posts_limit') > 0) {
				return $this->hasMany('App\Model\Post')->where('status', Post::APPROVED_STATUS);
			} else {
				return $this->hasMany('App\Model\Post');
			}
    }

    public function postComments()
    {
        return $this->hasMany('App\Model\PostComment');
    }

    public function reactions()
    {
        return $this->hasMany('App\Model\Reaction');
    }

    public function subscriptions()
    {
        return $this->hasMany('App\Model\Subscription');
    }

    public function activeSubscriptions()
    {
        return $this->hasMany('App\Model\Subscription', 'sender_user_id')->where('status', 'completed');
    }

    public function activeCanceledSubscriptions()
    {
        return $this->hasMany('App\Model\Subscription', 'sender_user_id')->where('status', 'canceled')->where('expire_at', '<', Carbon::now());
    }

    public function subscribers()
    {
        return $this->hasMany('App\Model\Subscription', 'recipient_user_id');
    }

    public function transactions()
    {
        return $this->hasMany('App\Model\Transaction');
    }

    public function withdrawals()
    {
        return $this->hasMany('App\Model\Withdrawal');
    }

    public function attachments()
    {
        return $this->hasMany('App\Model\Attachment');
    }

    public function lists()
    {
        return $this->hasMany('App\Model\UserList');
    }

    public function bookmarks()
    {
        return $this->hasMany('App\Model\UserBookmark');
    }

    public function wallet()
    {
        return $this->hasOne('App\Model\Wallet', 'user_id');
    }

    public function verification()
    {
        return $this->hasOne('App\Model\UserVerify');
    }

    public function offer()
    {
        return $this->hasOne('App\Model\CreatorOffer');
    }

    function parseMentions($text) {
        return preg_replace_callback('/@(\w+)/', function($matches) {
            $username = $matches[1];
            // Busca o usuário pelo nome de usuário (username)
            $user = User::where('username', $username)->first();
            
            if ($user) {
                // Cria o link para o perfil do usuário
                return '<a href="https://closyflix.com/' . $user->username . '">@' . $user->username . '</a>';
            }

            // Se o usuário não for encontrado, retorna o texto original
            return $matches[0];
        }, $text);
    }

}
