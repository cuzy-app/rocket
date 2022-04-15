<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\rocket\components;

use ATDev\RocketChat\Channels\Channel as RocketChannel;
use ATDev\RocketChat\Chat as RocketChat;
use ATDev\RocketChat\Groups\Group as RocketGroup;
use ATDev\RocketChat\Users\User as RocketUser;
use humhub\modules\rocket\models\ModuleSettings;
use Yii;
use yii\base\Component;
use yii\helpers\BaseInflector;


/**
 * Doc: https://github.com/alekseykuleshov/rocket-chat
 */
class RocketApi extends Component
{
    protected const CACHE_KEY_PREFIX = 'rocketApi';
    protected const CACHE_DURATION = 60 * 60;

    /**
     * @var ModuleSettings module settings
     * Populated in init() function
     */
    public $settings;

    /**
     * @var string[]
     */
    public $users;

    /**
     * @var string[]
     */
    public $groups;

    /**
     * @var string[]
     */
    public $channels;

    /**
     * @var bool
     */
    protected $loggedIn = false;


    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->settings = new ModuleSettings();

        if (!class_exists('Chat')) {
            require Yii::getAlias('@rocket/vendor/autoload.php');
        }

        // Login to Rocket API
        if ($this->settings->apiUrl && $this->settings->apiUserLogin && $this->settings->apiUserPassword) {
            RocketChat::setUrl($this->settings->apiUrl);
            $this->loggedIn = $this->resultIsValid(RocketChat::login($this->settings->apiUserLogin, $this->settings->apiUserPassword), __METHOD__, RocketChat::class);
        }

        parent::init();
    }

    /**
     * @param $result
     * @param string|null $methodName
     * @param string|null $class
     * @return bool
     */
    protected function resultIsValid($result, ?string $methodName = null, ?string $class = null)
    {
        if (!$result) {
            $error = $class ? $class::getError() : $result->getError();
            Yii::error('Rocket module error on API request' . ($methodName ? ' (' . $methodName . ')' : '') . ': ' . $error);
            return false;
        }
        return true;
    }

    /**
     * @param $rocketGroupName
     * @return bool
     */
    public function createGroup($rocketGroupName)
    {
        if (
            !$this->loggedIn
            || $this->groupNameToGroupId($rocketGroupName) // exists already
        ) {
            return false;
        }

        $group = new RocketGroup();
        $group->setName($rocketGroupName);
        $group->setReadOnlyValue(true);

        return $this->resultIsValid($group->create(), __METHOD__);
    }

    /**
     * @param $groupName
     * @return false|string
     */
    public function groupNameToGroupId($groupName)
    {
        return array_search(BaseInflector::slug($groupName), $this->getGroups(), true);
    }

    /**
     * @return string[]
     */
    public function getGroups($flushCache = false)
    {
        if (!$this->loggedIn) {
            return [];
        }

        if ($this->groups === null) {
            $cacheKey = static::CACHE_KEY_PREFIX . 'groups';
            if ($flushCache) {
                Yii::$app->cache->delete($cacheKey);
            }
            $this->groups = Yii::$app->cache->getOrSet($cacheKey, function () {
                $groupListing = RocketGroup::listing();
                if ($this->resultIsValid($groupListing, __METHOD__, RocketGroup::class)) {
                    $groups = [];
                    /** @var RocketGroup $group */
                    foreach ($groupListing as $group) {
                        $groups[$group->getGroupId()] = BaseInflector::slug($group->getName());
                    }
                    return $groups;
                }
                return [];
            }, static::CACHE_DURATION);
        }

        return $this->groups;
    }

    /**
     * @param $rocketGroupName
     * @return bool
     */
    public function deleteGroup($rocketGroupName)
    {
        if (
            !$this->loggedIn
            || !($groupId = $this->groupNameToGroupId($rocketGroupName))
        ) {
            return false;
        }

        $group = new RocketGroup($groupId);

        return $this->resultIsValid($group->delete(), __METHOD__);
    }

    /**
     * @param $rocketGroupName
     * @param $rocketGroupNewName
     * @return bool
     */
    public function renameGroup($rocketGroupName, $rocketGroupNewName)
    {
        if (
            !$this->loggedIn
            || !($groupId = $this->groupNameToGroupId($rocketGroupName))
        ) {
            return false;
        }

        $group = new RocketGroup($groupId);

        return $this->resultIsValid($group->rename($rocketGroupNewName), __METHOD__);
    }

    /**
     * @param $rocketUserUsername
     * @param $rocketGroupName
     * @return bool
     */
    public function inviteUserToGroup($rocketUserUsername, $rocketGroupName)
    {
        if (
            !$this->loggedIn
            || !($userId = $this->userUsernameToUserId($rocketUserUsername))
            || !($groupId = $this->groupNameToGroupId($rocketGroupName))
        ) {
            return false;
        }

        $user = new RocketUser($userId);
        $group = new RocketGroup($groupId);

        return $this->resultIsValid($group->invite($user), __METHOD__);
    }

    /**
     * @param $userUsername
     * @return false|string
     */
    public function userUsernameToUserId($userUsername)
    {
        return array_search(BaseInflector::slug($userUsername), $this->getUsers(), true);
    }

    /**
     * @return string[]
     */
    public function getUsers($flushCache = false)
    {
        if (!$this->loggedIn) {
            return [];
        }

        if ($this->users === null) {
            $cacheKey = static::CACHE_KEY_PREFIX . 'users';
            if ($flushCache) {
                Yii::$app->cache->delete($cacheKey);
            }
            $this->users = Yii::$app->cache->getOrSet($cacheKey, function () {
                $userListing = RocketUser::listing();
                if ($this->resultIsValid($userListing, __METHOD__, RocketUser::class)) {
                    $users = [];
                    /** @var RocketUser $user */
                    foreach ($userListing as $user) {
                        $users[$user->getUserId()] = BaseInflector::slug($user->getUsername());
                    }
                    return $users;
                }
                return [];
            }, static::CACHE_DURATION);
        }

        return $this->users;
    }

    /**
     * @param $rocketUserUsername
     * @param $rocketGroupName
     * @return bool
     */
    public function kickUserOutOfGroup($rocketUserUsername, $rocketGroupName)
    {
        if (
            !$this->loggedIn
            || !($userId = $this->userUsernameToUserId($rocketUserUsername))
            || !($groupId = $this->groupNameToGroupId($rocketGroupName))
        ) {
            return false;
        }

        $user = new RocketUser($userId);
        $group = new RocketGroup($groupId);

        return $this->resultIsValid($group->kick($user), __METHOD__);
    }

    /**
     * @param $rocketUserUsername
     * @param $rocketChannelName
     * @return bool
     */
    public function inviteUserToChannel($rocketUserUsername, $rocketChannelName)
    {
        if (
            !$this->loggedIn
            || !($userId = $this->userUsernameToUserId($rocketUserUsername))
            || !($channelId = $this->channelNameToChannelId($rocketChannelName))
        ) {
            return false;
        }

        $user = new RocketUser($userId);
        $channel = new RocketChannel($channelId);

        return $this->resultIsValid($channel->invite($user), __METHOD__);
    }

    /**
     * @param $channelName
     * @return false|string
     */
    public function channelNameToChannelId($channelName)
    {
        return array_search(BaseInflector::slug($channelName), $this->getChannels(), true);
    }

    /**
     * @return string[]
     */
    public function getChannels($flushCache = false)
    {
        if (!$this->loggedIn) {
            return [];
        }

        if ($this->channels === null) {
            $cacheKey = static::CACHE_KEY_PREFIX . 'channels';
            if ($flushCache) {
                Yii::$app->cache->delete($cacheKey);
            }
            $this->channels = Yii::$app->cache->getOrSet($cacheKey, function () {
                $channelListing = RocketChannel::listing();
                if ($this->resultIsValid($channelListing, __METHOD__, RocketChannel::class)) {
                    $channels = [];
                    /** @var RocketChannel $channel */
                    foreach ($channelListing as $channel) {
                        $channels[$channel->getChannelId()] = BaseInflector::slug($channel->getName());
                    }
                    return $channels;
                }
                return [];
            }, static::CACHE_DURATION);
        }

        return $this->channels;
    }

    /**
     * @param $rocketUserUsername
     * @param $rocketChannelName
     * @return bool
     */
    public function kickUserOutOfChannel($rocketUserUsername, $rocketChannelName)
    {
        if (
            !$this->loggedIn
            || !($userId = $this->userUsernameToUserId($rocketUserUsername))
            || !($channelId = $this->channelNameToChannelId($rocketChannelName))
        ) {
            return false;
        }

        $user = new RocketUser($userId);
        $channel = new RocketChannel($channelId);

        return $this->resultIsValid($channel->kick($user), __METHOD__);
    }

    /**
     * @return void
     */
    public function logout()
    {
        if ($this->loggedIn) {
            RocketChat::logout();
        }
    }
}