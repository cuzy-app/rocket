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
use ATDev\RocketChat\Roles\Role as RocketRole;
use ATDev\RocketChat\Users\User as RocketUser;
use humhub\modules\rocket\models\ModuleSettings;
use humhub\modules\rocket\Module;
use humhub\modules\user\models\User;
use Yii;
use yii\base\Component;
use yii\helpers\BaseInflector;


/**
 * Rocket.chat API PHP Wrapper Library doc: https://github.com/alekseykuleshov/rocket-chat
 * Rocket.chat API doc: https://developer.rocket.chat/reference/api/rest-api/endpoints/team-collaboration-endpoints
 *
 * Role on Rocket.chat is equivalent to groups in Humhub
 * Channel on Rocket.chat is a public channel
 * Group on Rocket.chat is a private channel
 */
class RocketApi extends Component
{
    public const ERRORS_TO_IGNORE = [
        '[error-user-already-in-role]',
        '[error-user-not-in-role]'
    ];

    protected const CACHE_KEY_PREFIX_USER = 'rocketApiUser';
    protected const CACHE_KEY_PREFIX_ROLE = 'rocketApiRole';
    protected const CACHE_KEY_PREFIX_CHANNEL = 'rocketApiChannel';
    protected const CACHE_KEY_PREFIX_GROUP = 'rocketApiGroup';
    protected const CACHE_DURATION = 60 * 60;

    /**
     * @var ModuleSettings module settings
     * Populated in init() function
     */
    public $settings;

    /**
     * @var string[]
     */
    public $rocketUserUsernames;

    /**
     * @var string[]|null
     */
    public $rocketUserEmails;

    /**
     * @var string[]|null
     */
    public $rocketRoleNames;

    /**
     * @var string[]|null
     */
    public $rocketChannelNames;

    /**
     * @var string[]|null
     */
    public $rocketGroupNames;

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
            $result = RocketChat::login($this->settings->apiUserLogin, $this->settings->apiUserPassword);
            $this->loggedIn = $this->resultIsValid($result, RocketChat::class, __METHOD__);
        }

        parent::init();
    }

    /**
     * @param $result
     * @param $classNameOrObject
     * @param string|null $methodName
     * @return bool
     */
    protected function resultIsValid($result, $classNameOrObject, ?string $methodName = null)
    {
        if (!$result) {
            $error = is_string($classNameOrObject) ? $classNameOrObject::getError() : $classNameOrObject->getError();
            $ignoreError = false;
            foreach (self::ERRORS_TO_IGNORE as $errorToIgnore) {
                if (strpos($error, $errorToIgnore) !== false) {
                    $ignoreError = true;
                    break;
                }
            }
            if (!$ignoreError) {
                Yii::error('Rocket module error on API request' . ($methodName ? ' (' . $methodName . ')' : '') . ': ' . $error);
            }
            return false;
        }
        return true;
    }

    /**
     * @param string $rocketRoleName
     * @return bool
     */
    public function createRole(string $rocketRoleName)
    {
        if (
            !$this->loggedIn
            || $this->getRocketRoleId($rocketRoleName) !== null // exists already
        ) {
            return false;
        }

        $rocketRole = new RocketRole();
        $rocketRole->setName(BaseInflector::slug($rocketRoleName));
        $rocketRole->setDescription($rocketRoleName);
        $result = $rocketRole->create();

        if ($this->resultIsValid($result, $rocketRole, __METHOD__)) {
            $this->initRocketRoleNames(true);
            $this->rocketRoleNames[$rocketRole->getRoleId()] = $rocketRole->getName();
            $this->updateRocketRoleNamesCache();
            return true;
        }
        return false;
    }

    /**
     * @param string $roleName
     * @return null|string
     */
    public function getRocketRoleId(string $roleName)
    {
        $this->initRocketRoleNames();
        return array_search(BaseInflector::slug($roleName), $this->rocketRoleNames, true) ?: null;
    }

    /**
     * @return void
     */
    public function initRocketRoleNames($flushCache = false)
    {
        if (!$this->loggedIn || $this->rocketRoleNames !== null) {
            return;
        }

        if ($flushCache) {
            Yii::$app->cache->delete(static::CACHE_KEY_PREFIX_ROLE);
        }
        $this->rocketRoleNames = Yii::$app->cache->getOrSet(static::CACHE_KEY_PREFIX_ROLE, function () {
            $roleListing = RocketRole::listing();
            if ($this->resultIsValid($roleListing, RocketRole::class, __METHOD__)) {
                $roles = [];
                /** @var RocketRole $role */
                foreach ($roleListing as $role) {
                    $roles[$role->getRoleId()] = BaseInflector::slug($role->getName());
                }
                return $roles;
            }
            return [];
        }, static::CACHE_DURATION);
    }

    /**
     * @return void
     */
    public function updateRocketRoleNamesCache()
    {
        Yii::$app->cache->delete(static::CACHE_KEY_PREFIX_ROLE);
        Yii::$app->cache->set(static::CACHE_KEY_PREFIX_ROLE, $this->rocketRoleNames, static::CACHE_DURATION);
    }

    /**
     * @param string $rocketRoleName
     * @return bool
     */
    public function deleteRole(string $rocketRoleName)
    {
        if (
            !$this->loggedIn
            || ($roleId = $this->getRocketRoleId($rocketRoleName)) === null
        ) {
            return false;
        }

        $rocketRole = (new RocketRole())->setRoleId($roleId);
        $result = $rocketRole->delete();

        if ($this->resultIsValid($result, $rocketRole, __METHOD__)) {
            $this->initRocketRoleNames(true);
            unset($this->rocketRoleNames[$roleId]);
            $this->updateRocketRoleNamesCache();
            return true;
        }
        return false;
    }

    /**
     * @param string $rocketRoleName
     * @param string $rocketRoleNewName
     * @return bool
     */
    public function renameRole(string $rocketRoleName, string $rocketRoleNewName)
    {
        if (
            !$this->loggedIn
            || ($roleId = $this->getRocketRoleId($rocketRoleName)) === null
        ) {
            return false;
        }

        $rocketRole = (new RocketRole())->setRoleId($roleId);
        $rocketRole->setName(BaseInflector::slug($rocketRoleNewName));
        $result = $rocketRole->update();

        if ($this->resultIsValid($result, $rocketRole, __METHOD__)) {
            $this->initRocketRoleNames(true);
            $this->rocketRoleNames[$roleId] = $rocketRole->getName();
            $this->updateRocketRoleNamesCache();
            return true;
        }
        return false;
    }

    /**
     * @param User|int|string $user
     * @param string $rocketRoleName
     * @return bool
     */
    public function addUserToRole($user, string $rocketRoleName)
    {
        $user = $this->convertUser($user);
        if (
            !$this->loggedIn
            || ($userId = $this->getRocketUserId($user)) === null
        ) {
            return false;
        }

        // Create missing role
        if ($this->getRocketRoleId($rocketRoleName) === null) {
            $this->createRole($rocketRoleName);
        }

        $rocketUserUsername = $this->rocketUserUsernames[$userId];
        $rocketRole = (new RocketRole())->setName(BaseInflector::slug($rocketRoleName));
        $result = $rocketRole->addUserToRole($rocketUserUsername);

        return $this->resultIsValid($result, $rocketRole, __METHOD__);
    }

    /**
     * Search Rocket User ID from Humhub email and username
     * @param User $humhubUser
     * @return null|string
     */
    public function getRocketUserId(User $humhubUser)
    {
        $this->initRocketUsers();
        $rocketUserId = array_search(trim($humhubUser->username), $this->rocketUserUsernames, true) ?: null;
        if ($rocketUserId !== false) {
            return $rocketUserId;
        }
        return array_search(trim($humhubUser->email), $this->rocketUserEmails, true) ?: null;
    }

    /**
     * @return void
     */
    public function initRocketUsers($flushCache = false)
    {
        if (
            !$this->loggedIn
            || ($this->rocketUserUsernames !== null && $this->rocketUserEmails !== null)
        ) {
            return;
        }

        if ($flushCache) {
            Yii::$app->cache->delete(static::CACHE_KEY_PREFIX_USER);
        }
        $users = Yii::$app->cache->getOrSet(static::CACHE_KEY_PREFIX_USER, function () {
            $userListing = RocketUser::listing();
            if ($this->resultIsValid($userListing, RocketUser::class, __METHOD__)) {
                $users = [];
                /** @var RocketUser $user */
                foreach ($userListing as $user) {
                    $users[$user->getUserId()] = [
                        'username' => $user->getUsername(),
                        'email' => trim($user->getEmail()),
                    ];
                }
                return $users;
            }
            return [];
        }, static::CACHE_DURATION);

        $this->rocketUserUsernames = [];
        $this->rocketUserEmails = [];
        foreach ($users as $userId => $user) {
            $this->rocketUserUsernames[$userId] = $user['username'];
            $this->rocketUserEmails[$userId] = $user['email'];
        }
    }

    /**
     * @param User|int|string $user
     * @param string $rocketRoleName
     * @return bool
     */
    public function removeUserFromRole($user, string $rocketRoleName)
    {
        $user = $this->convertUser($user);
        if (
            !$this->loggedIn
            || ($userId = $this->getRocketUserId($user)) === null
            || $this->getRocketRoleId($rocketRoleName) === null
        ) {
            return false;
        }

        $rocketUserUsername = $this->rocketUserUsernames[$userId];
        $rocketRole = (new RocketRole())->setName(BaseInflector::slug($rocketRoleName));
        $result = $rocketRole->removeUserFromRole($rocketUserUsername);

        return $this->resultIsValid($result, $rocketRole, __METHOD__);
    }

    /**
     * @param User|int|string $user
     * @param string $channelId
     * @return bool
     */
    public function inviteUserToChannel($user, string $channelId)
    {
        $user = $this->convertUser($user);
        if (
            !$this->loggedIn
            || ($userId = $this->getRocketUserId($user)) === null
            || !$this->rocketChannelIdExists($channelId)
        ) {
            return false;
        }

        $rocketUser = new RocketUser($userId);
        $rocketChannel = new RocketChannel($channelId);
        $result = $rocketChannel->invite($rocketUser);

        return $this->resultIsValid($result, $rocketChannel, __METHOD__);
    }

    /**
     * @param User|int|string $user
     * @param string $groupId
     * @return bool
     */
    public function inviteUserToGroup($user, string $groupId)
    {
        $user = $this->convertUser($user);
        if (
            !$this->loggedIn
            || ($userId = $this->getRocketUserId($user)) === null
            || !$this->rocketGroupIdExists($groupId)
        ) {
            return false;
        }

        $rocketUser = new RocketUser($userId);
        $rocketGroup = new RocketGroup($groupId);
        $result = $rocketGroup->invite($rocketUser);

        return $this->resultIsValid($result, $rocketGroup, __METHOD__);
    }

    /**
     * @param $channelName
     * @return null|string
     */
    public function getRocketChannelId($channelName)
    {
        $this->initRocketChannelNames();
        return array_search(BaseInflector::slug($channelName), $this->rocketChannelNames, true) ?: null;
    }

    /**
     * @param $rocketChannelId
     * @return bool
     */
    public function rocketChannelIdExists($rocketChannelId)
    {
        $this->initRocketChannelNames();
        return array_key_exists($rocketChannelId, $this->rocketChannelNames);
    }

    /**
     * @param $rocketGroupId
     * @return bool
     */
    public function rocketGroupIdExists($rocketGroupId)
    {
        $this->initRocketGroupNames();
        return array_key_exists($rocketGroupId, $this->rocketGroupNames);
    }

    /**
     * @param $groupName
     * @return null|string
     */
    public function getRocketGroupId($groupName)
    {
        $this->initRocketGroupNames();
        return array_search(BaseInflector::slug($groupName), $this->rocketGroupNames, true) ?: null;
    }

    /**
     * @return void
     */
    public function initRocketChannelNames($flushCache = false)
    {
        if (!$this->loggedIn || $this->rocketChannelNames !== null) {
            return;
        }

        if ($flushCache) {
            Yii::$app->cache->delete(static::CACHE_KEY_PREFIX_CHANNEL);
        }
        $this->rocketChannelNames = Yii::$app->cache->getOrSet(static::CACHE_KEY_PREFIX_CHANNEL, function () {
            $channelListing = RocketChannel::listing();
            if ($this->resultIsValid($channelListing, RocketChannel::class, __METHOD__)) {
                $channels = [];
                /** @var RocketChannel $channel */
                foreach ($channelListing as $channel) {
                    $channels[$channel->getChannelId()] = BaseInflector::slug($channel->getName());
                }

                // Save to module's settings
                /** @var Module $module */
                $module = Yii::$app->getModule('rocket');
                $settings = $module->settings;
                $settings->setSerialized('rocketChannelNames', $channels);

                return $channels;
            }
            return [];
        }, static::CACHE_DURATION);
    }

    /**
     * @return void
     */
    public function initRocketGroupNames($flushCache = false)
    {
        if (!$this->loggedIn || $this->rocketGroupNames !== null) {
            return;
        }

        if ($flushCache) {
            Yii::$app->cache->delete(static::CACHE_KEY_PREFIX_GROUP);
        }
        $this->rocketGroupNames = Yii::$app->cache->getOrSet(static::CACHE_KEY_PREFIX_GROUP, function () {
            $groupListing = RocketGroup::listing();
            if ($this->resultIsValid($groupListing, RocketGroup::class, __METHOD__)) {
                $groups = [];
                /** @var RocketGroup $group */
                foreach ($groupListing as $group) {
                    $groups[$group->getGroupId()] = BaseInflector::slug($group->getName());
                }

                // Save to module's settings
                /** @var Module $module */
                $module = Yii::$app->getModule('rocket');
                $settings = $module->settings;
                $settings->setSerialized('rocketGroupNames', $groups);

                return $groups;
            }
            return [];
        }, static::CACHE_DURATION);
    }

    /**
     * @param User|int|string $user
     * @param string $channelId
     * @return bool
     */
    public function kickUserOutOfChannel($user, string $channelId)
    {
        $user = $this->convertUser($user);
        if (
            !$this->loggedIn
            || ($userId = $this->getRocketUserId($user)) === null
            || !$this->rocketChannelIdExists($channelId)
        ) {
            return false;
        }

        $rocketUser = new RocketUser($userId);
        $rocketChannel = new RocketChannel($channelId);
        $result = $rocketChannel->kick($rocketUser);

        return $this->resultIsValid($result, $rocketChannel, __METHOD__);
    }

    /**
     * @param User|int|string $user
     * @param string $groupId
     * @return bool
     */
    public function kickUserOutOfGroup($user, string $groupId)
    {
        $user = $this->convertUser($user);
        if (
            !$this->loggedIn
            || ($userId = $this->getRocketUserId($user)) === null
            || !$this->rocketGroupIdExists($groupId)
        ) {
            return false;
        }

        $rocketUser = new RocketUser($userId);
        $rocketGroup = new RocketGroup($groupId);
        $result = $rocketGroup->kick($rocketUser);

        return $this->resultIsValid($result, $rocketGroup, __METHOD__);
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

    /**
     * @param User|int|string $user
     * @return User|null
     */
    protected function convertUser($user)
    {
        if ($user instanceof User) {
            return $user;
        }
        return User::findOne($user);
    }
}