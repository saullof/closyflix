<?php

namespace App\Providers;

use App\Model\Attachment;
use App\Model\PaymentRequest;
use App\Model\Post;
use App\Model\Stream;
use App\Model\Subscription;
use App\Model\Transaction;
use App\Model\UserMessage;
use App\Model\UserVerify;
use App\Model\Withdrawal;
use App\Observers\AttachmentsObserver;
use App\Observers\PaymentRequestsObserver;
use App\Observers\PostApprovalObserver;
use App\Observers\StreamsObserver;
use App\Observers\SubscriptionsObserver;
use App\Observers\TransactionsObserver;
use App\Observers\UserMessagesObserver;
use App\Observers\UsersObserver;
use App\Observers\UserVerifyObserver;
use App\Observers\WithdrawalsObserver;
use App\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (! InstallerServiceProvider::checkIfInstalled()) {
            return false;
        }
    
        // Observers
        UserVerify::observe(UserVerifyObserver::class);
        Withdrawal::observe(WithdrawalsObserver::class);
        PaymentRequest::observe(PaymentRequestsObserver::class);
        UserMessage::observe(UserMessagesObserver::class);
        Attachment::observe(AttachmentsObserver::class);
        Transaction::observe(TransactionsObserver::class);
        Post::observe(PostApprovalObserver::class);
        Subscription::observe(SubscriptionsObserver::class);
        User::observe(UsersObserver::class);
        Stream::observe(StreamsObserver::class);
    
        // SSL enforcement
        if(getSetting('security.enforce_app_ssl')) {
            \URL::forceScheme('https');
        }
    
        Schema::defaultStringLength(191); // TODO: Maybe move it as the first line
        if(!InstallerServiceProvider::glck()) {
            dd(base64_decode('SW52YWxpZCBzY3JpcHQgc2lnbmF0dXJl'));
        }
    
        // Timezone settings
        if(getSetting('site.timezone')) {
            config(['app.timezone' => getSetting('site.timezone')]);
            date_default_timezone_set(getSetting('site.timezone'));
        }
    
        // Paginator
        Paginator::useBootstrap();
    
        // **Adicionando View Composer**
        \Illuminate\Support\Facades\View::composer('voyager::index', function ($view) {
            $totalSaques = \DB::table('withdrawals')->where('status', 'requested')->count();
            $totalPerfisPendentes = \DB::table('user_verifies')->where('status', 'pending')->count();
    
            $view->with([
                'totalSaques' => $totalSaques,
                'totalPerfisPendentes' => $totalPerfisPendentes,
            ]);
        });
    }
    
}
