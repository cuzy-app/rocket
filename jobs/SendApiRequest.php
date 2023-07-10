<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\rocket\jobs;


use humhub\modules\queue\ActiveJob;
use humhub\modules\rocket\components\RocketApi;
use Yii;
use yii\queue\RetryableJobInterface;

class SendApiRequest extends ActiveJob implements RetryableJobInterface
{
    /**
     * @var string
     */
    public $method;

    /**
     * @var array
     */
    public $arguments = [];

    /**
     * @inhertidoc
     */
    private $maxExecutionTime = 3 * 60;

    /**
     * @inheritdoc
     */
    public function run()
    {
        $api = new RocketApi();
        call_user_func_array([$api, $this->method], $this->arguments);
        $api->logout();
    }

    /**
     * @inheritDoc
     */
    public function getTtr()
    {
        return $this->maxExecutionTime;
    }

    /**
     * @inheritDoc
     */
    public function canRetry($attempt, $error)
    {
        $errorMessage = $error ? $error->getMessage() : '';
        Yii::error('Error with SendApiRequest job: ' . $errorMessage, 'rocket');
        return false;
    }

}
