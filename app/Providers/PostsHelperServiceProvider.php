<?php

namespace App\Providers;

use App\Model\Attachment;
use App\Model\Post;
use App\Model\PostComment;
use App\Model\Stream;
use App\Model\Subscription;
use App\Model\Transaction;
use App\Model\UserList;
use App\User;
use Carbon\Carbon;
use Cookie;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use View;

class PostsHelperServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Get latest user attachments.
     *
     * @param bool $userID
     * @param bool $type
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     * @throws \Exception
     */
    public static function getLatestUserAttachments($userID = false, $type = false)
    {
        if (! $userID) {
            if (Auth::check()) {
                $userID = Auth::user()->id;
            } else {
                throw new \Exception(__('Can not fetch latest post attachments for this profile.'));
            }
        }
        $attachments = Attachment::with(['post'])->where('attachments.post_id', '<>', null)->where('attachments.user_id', $userID);

        if ($type) {
            $extensions = AttachmentServiceProvider::getTypeByExtension('image');
            $attachments->whereIn('attachments.type', $extensions);
        }
        // validate access for paid posts attachments
        if(Auth::check() && Auth::user()->role_id !== 1 && Auth::user()->id !== $userID) {
            $attachments->leftJoin('posts', 'posts.id', '=', 'attachments.post_id')
                ->leftJoin('transactions', 'transactions.post_id', '=', 'posts.id')
                ->where(function ($query) {
                    $query->where('posts.price', '=', floatval(0))
                        ->orWhere(function ($query) {
                            $query->where('transactions.id', '<>', null)
                                ->where('transactions.type', '=', Transaction::POST_UNLOCK)
                                ->where('transactions.status', '=', Transaction::APPROVED_STATUS)
                                ->where('transactions.sender_user_id', '=', Auth::user()->id);
                        });
                })
                ->where(function($query) {
                    $query->where('posts.expire_date', '>', Carbon::now());
                    $query->orWhere('posts.expire_date', null);
                })
                ->where(function($query) {
                    $query->where('posts.release_date', '<', Carbon::now());
                    $query->orWhere('posts.release_date',null);
                })
                ->where('posts.status', 1);
        }
        $attachments = $attachments->limit(3)->orderByDesc('attachments.created_at')->get();

        return $attachments;
    }

    /**
     * Get user by it's username.
     *
     * @param $username
     * @return mixed
     */
    public static function getUserByUsername($username)
    {
        return User::where('username', $username)->first();
    }

    /**
     * Get user's all active subs.
     *
     * @param $userID
     * @return mixed
     */
    public static function getUserActiveSubs($userID)
    {
        $activeSubs = Subscription::where('sender_user_id', $userID)
            ->where(function ($query) {
                $query->where('status', 'completed')
                    ->orwhere([
                        ['status', '=', 'canceled'],
                        ['expires_at', '>', Carbon::now()->toDateTimeString()],
                    ]);
            })
            ->get()
            ->pluck('recipient_user_id')->toArray();

        return $activeSubs;
    }

    /**
     * Get following users with free profiles
     * @param $userId
     * @return mixed
     */
    public static function getFreeFollowingProfiles($userId){
        $followingList = UserList::where('user_id', $userId)->where('type', 'following')->with(['members','members.user'])->first();
        $followingUserIds = [];
        foreach($followingList->members as $member){
            if(!$member->user->paid_profile || (getSetting('profiles.allow_users_enabling_open_profiles') && $member->user->open_profile)){
                $followingUserIds[] =  $member->user->id;
            }
        }
        return $followingUserIds;
    }

    /**
     * Check if user has active sub to another.
     *
     * @param $sender_id
     * @param $recipient_id
     * @return bool
     */
    public static function hasActiveSub($sender_id, $recipient_id)
    {
        $hasSub = Subscription::where('sender_user_id', $sender_id)
            ->where('recipient_user_id', $recipient_id)
            ->where(function ($query) {
                $query->where('status', 'completed')
                    ->orwhere([
                        ['status', '=', 'canceled'],
                        ['expires_at', '>', Carbon::now()->toDateTimeString()],
                    ]);
            })
            ->count();
        if ($hasSub > 0) {
            return true;
        }

        return false;
    }

    /**
     * Gets list of posts for feed.
     * @param $userID
     * @param bool $encodePostsToHtml
     * @param bool $pageNumber
     * @param bool $mediaType
     * @return array
     */
    public static function getFeedPosts($userID, $encodePostsToHtml = false, $pageNumber = false, $mediaType = false, $sortOrder = false, $searchTerm = '')
    {
        return self::getFilteredPosts($userID, $encodePostsToHtml, $pageNumber, $mediaType, false, false, false, $sortOrder, $searchTerm);
    }

    /**
     * Gets list of posts for profile.
     * @param $userID
     * @param bool $encodePostsToHtml
     * @param bool $pageNumber
     * @param bool $mediaType
     * @return array
     */
    public static function getUserPosts($userID, $encodePostsToHtml = false, $pageNumber = false, $mediaType = false, $hasSub = false, $paidFilter = 'all')
    {
        return self::getFilteredPosts($userID, $encodePostsToHtml, $pageNumber, $mediaType, true, $hasSub, false, false, '', $paidFilter);
    }
    

    /**
     * Gets list of posts for the bookmarks page.
     * @param $userID
     * @param bool $encodePostsToHtml
     * @param bool $pageNumber
     * @param bool $mediaType
     * @return array
     */
    public static function getUserBookmarks($userID, $encodePostsToHtml = false, $pageNumber = false, $mediaType = false, $hasSub = false)
    {
        return self::getFilteredPosts($userID, $encodePostsToHtml, $pageNumber, $mediaType, false, $hasSub, true);
    }

    /**
     * Returns lists of posts, conditioned by different filters.
     * TODO: This one should get refactored a little bit - eg: remove all un-necessary params to differ between these feed pages:
     * feed - profile (logged in/not logged in) - search - bookmarks
     * @param $userID
     * @param bool $encodePostsToHtml
     * @param bool $pageNumber
     * @param bool $mediaType
     * @return array
     */
    public static function getFilteredPosts($userID, $encodePostsToHtml, $pageNumber, $mediaType, $ownPosts, $hasSub, $bookMarksOnly, $sortOrder = false, $searchTerm = '',$paidFilter = 'all')
    {
        $relations = ['user', 'reactions', 'attachments', 'bookmarks', 'postPurchases'];

        // Fetching basic posts information
        $posts = Post::withCount('tips')
            ->with($relations);

        // Filtro por posts pagos ou gratuitos
        if ($paidFilter === 'paid') {
            $posts->where('price', '>', 0);
        } elseif ($paidFilter === 'free') {
            $posts->where('price', '=', 0);
        }


        // For profile page
        if ($ownPosts) {
            $posts->where('user_id', $userID);
            // Registered
            if(Auth::check() && Auth::user()->id !== $userID) {
                $posts = self::filterPosts($posts, $userID, 'scheduled');
                $posts = self::filterPosts($posts, $userID, 'approvedPostsOnly');
            }
            // Un-registered
            elseif (!Auth::check()){
                $posts = self::filterPosts($posts, $userID, 'scheduled');
                $posts = self::filterPosts($posts, $userID, 'approvedPostsOnly');
            }
            $posts = self::filterPosts($posts, $userID, 'pinned');
        }
        // For bookmarks page
        elseif ($bookMarksOnly) {
            $posts = self::filterPosts($posts, $userID, 'bookmarks');
            $posts = self::filterPosts($posts, $userID, 'blocked');
        }
        // For feed page
        else {
            $posts = self::filterPosts($posts, $userID, 'all');
        }

        if (!$ownPosts) { // More feed/bookmarks/search rules
            $posts = self::filterPosts($posts, $userID, 'scheduled');
            $posts = self::filterPosts($posts, $userID, 'approvedPostsOnly');
        }

        // Media type filters
        if ($mediaType) {
            $posts = self::filterPosts($posts, $userID, 'media', $mediaType);
        }

        // Filtering the search term
        if($searchTerm){
            $posts = self::filterPosts($posts, $userID, 'search',false,false,$searchTerm);
        }

        // Processing sorting
        $posts = self::filterPosts($posts, $userID, 'order',false,$sortOrder);

        if ($pageNumber) {
            $posts = $posts->paginate(getSetting('feed.feed_posts_per_page'), ['*'], 'page', $pageNumber)->appends(request()->query());
        } else {
            $posts = $posts->paginate(getSetting('feed.feed_posts_per_page'))->appends(request()->query());
        }

        if(Auth::check() && Auth::user()->role_id === 1){
            $hasSub = true;
        }

        if ($encodePostsToHtml) {
            // Posts encoded as JSON
            $data = [
                'total' => $posts->total(),
                'currentPage' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'prev_page_url' => $posts->previousPageUrl(),
                'next_page_url' => $posts->nextPageUrl(),
                'first_page_url' => $posts->nextPageUrl(),
                'hasMore' => $posts->hasMorePages(),
            ];
            $postsData = $posts->map(function ($post) use ($hasSub, $ownPosts, $data) {
                if ($ownPosts) {
                    $post->setAttribute('isSubbed', $hasSub);
                } else {
                    $post->setAttribute('isSubbed', true);
                }
                $post->setAttribute('postPage',$data['currentPage']);
                $post = ['id' => $post->id, 'html' => View::make('elements.feed.post-box')->with('post', $post)->render()];

                return $post;
            });
            $data['posts'] = $postsData;
        } else {
            // Collection data posts | To be rendered on the server side
            $postsCurrentPage = $posts->currentPage();
            $posts->map(function ($post) use ($hasSub, $ownPosts, $postsCurrentPage) {
                if ($ownPosts) {
                    $post->hasSub = $hasSub;
                    $post->setAttribute('isSubbed', $hasSub);
                } else {
                    $post->setAttribute('isSubbed', true);
                }
                $post->setAttribute('postPage',$postsCurrentPage);
                return $post;
            });
            $data = $posts;
        }

        return $data;
    }

    /**
     * Filters out posts using fast, join based queries.
     * @param $posts
     * @param $userID
     * @param $filterType
     * @param bool $mediaType
     * @return mixed
     */
    public static function filterPosts($posts, $userID, $filterType, $mediaType = false, $sortOrder = false, $searchTerm = '')
    {
        if ($filterType == 'following' || $filterType == 'all') {
            // Followers only
            $posts->join('user_list_members as following', function ($join) use ($userID) {
                $join->on('following.user_id', '=', 'posts.user_id');
                $join->on('following.list_id', '=', DB::raw(Auth::user()->lists->firstWhere('type', 'following')->id));
            });
        }

        if ($filterType == 'blocked' || $filterType == 'all') {
            // Blocked users
            $blockedUsers = ListsHelperServiceProvider::getListMembers(Auth::user()->lists->firstWhere('type', 'blocked')->id);
            $posts->whereNotIn('posts.user_id', $blockedUsers);
        }

        if ($filterType == 'subs' || $filterType == 'all') {
            if($filterType == 'all'){
                $userIds = array_merge(self::getUserActiveSubs($userID), self::getFreeFollowingProfiles($userID));
                $posts->whereIn('posts.user_id', $userIds);
            } else {
                // Subs only
                $activeSubs = self::getUserActiveSubs($userID);
                $posts->whereIn('posts.user_id', $activeSubs);
            }
        }

        if ($filterType == 'bookmarks') {
            $posts->join('user_bookmarks', function ($join) use ($userID) {
                $join->on('user_bookmarks.post_id', '=', 'posts.id');
                $join->on('user_bookmarks.user_id', '=', DB::raw($userID));
            });
        }

        if ($filterType == 'media') {
            // This guy is not really that optimal but neither bookmarks is heavy accessed
            $mediaTypes = AttachmentServiceProvider::getTypeByExtension($mediaType);
            $posts->whereHas('attachments', function ($query) use ($mediaTypes) {
                $query->whereIn('type', $mediaTypes);
            });
        }

        if ($filterType == 'search'){
            $posts->where(
                function($query) use ($searchTerm){
                    $query->where('text', 'like', '%'.$searchTerm.'%')
                        ->orWhereHas('user', function($q) use ($searchTerm) {
                            $q->where('username', 'like', '%'.$searchTerm.'%');
                            $q->orWhere('name', 'like', '%'.$searchTerm.'%');
                        });
                }
            );
        }

        if ($filterType == 'pinned'){
            $posts->orderBy('is_pinned','DESC');
        }

        if ($filterType == 'order') {
            if ($sortOrder) {
                if ($sortOrder == 'top') {
                    $relationsCount = ['reactions', 'comments'];
                    $posts->withCount($relationsCount);
                    $posts->orderBy('comments_count', 'DESC');
                    $posts->orderBy('reactions_count', 'DESC');
                } elseif ($sortOrder == 'latest') {
                    $posts->orderBy('posts.created_at', 'DESC'); // Especifica a tabela
                }
            } else {
                $posts->orderBy('posts.created_at', 'DESC'); // Especifica a tabela
            }
        }
        
        

        if ($filterType == 'scheduled') {
            $posts->notExpiredAndReleased();
        }

        if ($filterType == 'approvedPostsOnly') {
            if (!(Auth::check() && (Auth::user()->role_id === 1))) { // Admin can preview all  types of posts
                $posts->where('status', Post::APPROVED_STATUS);
            }
        }

        return $posts;
    }

    /**
     * Returns all comments for a post.
     * @param $post_id
     * @param int $limit
     * @param string $order
     * @param bool $encodePostsToHtml
     * @return array|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getPostComments($post_id, $limit = 9, $order = 'DESC', $encodePostsToHtml = false)
    {
        $comments = PostComment::with(['author', 'reactions'])->orderBy('created_at', $order)->where('post_id', $post_id)->paginate($limit);

        if ($encodePostsToHtml) {
            $data = [
                'total' => $comments->total(),
                'currentPage' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'prev_page_url' => $comments->previousPageUrl(),
                'next_page_url' => $comments->nextPageUrl(),
                'first_page_url' => $comments->nextPageUrl(),
                'hasMore' => $comments->hasMorePages(),
            ];
            $commentsData = $comments->map(function ($comment) {
                $post = ['id' => $comment->id, 'post_id' => $comment->post->id, 'html' => View::make('elements.feed.post-comment')->with('comment', $comment)->render()];

                return $post;
            });
            $data['comments'] = $commentsData;
        } else {
            $data = $comments;
        }

        return $data;
    }

    /**
     * Check if user has unlocked a post.
     * @param $transactions
     * @return bool
     */
    public static function hasUserUnlockedPost($transactions)
    {
        if (Auth::check()) {
            if(Auth::user()->role_id === 1) {
                return true;
            }

            foreach ($transactions as $transaction) {
                if (Auth::user()->id == $transaction->sender_user_id) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Check if user reacted to a post / comment.
     * @param $reactions
     * @return bool
     */
    public static function didUserReact($reactions)
    {
        if (Auth::check()) {
            foreach ($reactions as $reaction) {
                if (Auth::user()->id == $reaction->user_id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if post is bookmarked by current user.
     * @param $bookmarks
     * @return bool
     */
    public static function isPostBookmarked($bookmarks)
    {
        if (Auth::check()) {
            foreach ($bookmarks as $bookmark) {
                if (Auth::user()->id == $bookmark->user_id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user is coming back to a paginated feed post from a post page.
     * @param $page
     * @return bool
     */
    public static function isComingFromPostPage($page)
    {
        if (isset($page) && is_int(strpos($page['url'], '/posts')) && ! is_int(strpos($page['url'], '/posts/create'))) {
            return true;
        }

        return false;
    }

    /**
     * Get (user session) start page of the feed pagination.
     * @param $prevPage
     * @return int
     */
    public static function getFeedStartPage($prevPage)
    {
        return Cookie::get('app_feed_prev_page') && self::isComingFromPostPage($prevPage) ? Cookie::get('app_feed_prev_page') : 1;
    }

    /**
     * Get (user session) prev page of the feed pagination.
     * @param $request
     * @return mixed
     */
    public static function getPrevPage($request)
    {
        return $request->session()->get('_previous');
    }

    /**
     * Check if the pagination cookie should be deleted when navigating.
     * @param $request
     * @return bool
     */
    public static function shouldDeletePaginationCookie($request)
    {
        if (! self::isComingFromPostPage(self::getPrevPage($request))) {
            Cookie::queue(Cookie::forget('app_feed_prev_page'));
            Cookie::queue(Cookie::forget('app_prev_post'));
            return true;
        }

        return false;
    }

    /**
     * Returns count of each attachment types for user.
     * @param $userID
     * @return array
     */
    public static function getUserMediaTypesCount($userID)
    {
        $attachments = Attachment::
        leftJoin('posts', 'posts.id', '=', 'attachments.post_id')
            ->where('attachments.user_id', $userID)->where('post_id', '<>', null)
            ->where(function($query) {
                $query->where('posts.expire_date', '>', Carbon::now());
                $query->orWhere('posts.expire_date', null);
            })
            ->where(function($query) {
                $query->where('posts.release_date', '<', Carbon::now());
                $query->orWhere('posts.release_date',null);
            })
            ->get();
        $typeCounts = [
            'video' => 0,
            'audio' => 0,
            'image' => 0,
        ];
        foreach ($attachments as $attachment) {
            $typeCounts[AttachmentServiceProvider::getAttachmentType($attachment->type)] += 1;
        }
        $streams = Stream::where('user_id',$userID)->where('is_public',1)->whereIn('status',[Stream::ENDED_STATUS,Stream::IN_PROGRESS_STATUS])->count();
        $typeCounts['streams'] = $streams;
        return $typeCounts;
    }

    /**
     * Check if user paid for post
     * @param $userId
     * @param $postId
     * @return bool
     */
    public static function userPaidForPost($userId, $postId){
        return Transaction::query()->where(
                [
                    'post_id' => $postId,
                    'sender_user_id' => $userId,
                    'type' => Transaction::POST_UNLOCK,
                    'status' => Transaction::APPROVED_STATUS
                ]
            )->first() != null;
    }

    /**
     * Check if user paid for stream access
     * @param $userId
     * @param $streamId
     * @return bool
     */
    public static function userPaidForStream($userId, $streamId){
        return Transaction::query()->where(
                [
                    'stream_id' => $streamId,
                    'sender_user_id' => $userId,
                    'type' => Transaction::STREAM_ACCESS,
                    'status' => Transaction::APPROVED_STATUS
                ]
            )->first() != null;
    }

    /**
     * Checks if user paid access for this message
     * @param $userId
     * @param $messageId
     * @return bool
     */
    public static function userPaidForMessage($userId, $messageId){
        return Transaction::query()->where(
                [
                    'user_message_id' => $messageId,
                    'sender_user_id' => $userId,
                    'type' => Transaction::MESSAGE_UNLOCK,
                    'status' => Transaction::APPROVED_STATUS
                ]
            )->first() != null;
    }


    /**
     * Returns number of approved posts
     * @param $userID
     * @return mixed
     */
    public static function getUserApprovedPostsCount($userID){
        return $postsCount = Post::where([
            'user_id' =>  $userID,
            'status' => Post::APPROVED_STATUS
        ])->count();
    }

    public static function getPostsCountLeftTillAutoApprove($userID){
        return (int)getSetting('compliance.admin_approved_posts_limit') - self::getUserApprovedPostsCount(Auth::user()->id);
    }

    /**
     * Returns the default status for post to be created
     * If admin_approved_posts_limit is > 0, user must have had more posts than that number
     * Otherwise, post goes to pending state
     * @return int
     */
    public static function getDefaultPostStatus($userID){
        $postStatus = Post::APPROVED_STATUS;
        if(getSetting('compliance.admin_approved_posts_limit')){
            $postsCount = self::getUserApprovedPostsCount($userID);
            if((int)getSetting('compliance.admin_approved_posts_limit') > $postsCount){
                $postStatus = Post::PENDING_STATUS;
            }
        }
        return $postStatus;
    }

    /**
     * Counts types of media for a post attachments
     * @param $attachments
     * @return array
     */
    public static function getAttachmentsTypesCount($attachments){
        $counts = [
            'image' => 0,
            'video' => 0,
            'audio' => 0
        ];
        foreach($attachments as $attachment){
            AttachmentServiceProvider::getAttachmentType($attachment->type);
            if(isset($counts[AttachmentServiceProvider::getAttachmentType($attachment->type)])){
                $counts[AttachmentServiceProvider::getAttachmentType($attachment->type)] += 1;
            }
        }
        return $counts;
    }

}

