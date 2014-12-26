<?php namespace Strimoid\Http\ViewComposers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Group;

class MasterComposer {

    /**
     * Create a new profile composer.
     */
    public function __construct()
    {
    }

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $data = $view->getData();

        $assetsHost = env('development') ? '' : 'http://static.strimoid.pl';

        $view->with('cssFilename', $assetsHost . Config::get('assets.style.css'));
        $view->with('jsFilename', $assetsHost . Config::get('assets.app.js'));
        $view->with('componentsFilename', $assetsHost . Config::get('assets.components.js'));

        if (Auth::check())
        {
            // Load last 15 notifications
            $notifications = Notification::with(['sourceUser' => function($q) { $q->select('avatar')->remember(3); }])
                ->target(['user_id' => Auth::id()])
                ->orderBy('created_at', 'desc')
                ->take(15)->get();

            $view->with('notifications', $notifications);

            // And check how much unread notifications user has
            $elemMatch = ['user_id' => Auth::id(), 'read' => false];

            $newNotificationsCount = Notification::target($elemMatch)->count();

            $view->with('newNotificationsCount', $newNotificationsCount);
        }

        // Get object from which we can extract name to use as page title
        $currentGroup = head(array_only($data, ['group', 'folder', 'fakeGroup']));

        $view->with('currentGroup', $currentGroup);

        if (isset($currentGroup) && isset($currentGroup->name))
        {
            $pageTitle = $currentGroup->name;

            // Homepage title shall always be Strimoid.pl
            if ($currentGroup->urlname == 'all' && !Settings::get('homepage_subscribed'))
            {
                $pageTitle = 'Strimoid';
            }

            if ($currentGroup->urlname == 'subscribed' && Settings::get('homepage_subscribed'))
            {
                $pageTitle = 'Strimoid';
            }
        }
        else
        {
            $pageTitle = 'Strimoid';
        }

        $view->with('pageTitle', $pageTitle);

        // Needed by top bar with groups
        $popularGroups = Group::orderBy('subscribers', 'desc')
            ->take(30)->get();

        $view->with('popularGroups', $popularGroups);
    }

}