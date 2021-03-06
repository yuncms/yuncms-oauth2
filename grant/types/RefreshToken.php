<?php
/**
 * @link https://github.com/borodulin/yii2-oauth2-server
 * @copyright Copyright (c) 2015 Andrey Borodulin
 * @license https://github.com/borodulin/yii2-oauth2-server/blob/master/LICENSE
 */

namespace yuncms\oauth2\grant\types;

use Yii;
use yuncms\oauth2\models\AccessToken;
use yuncms\oauth2\BaseModel;

/**
 *
 * @author Andrey Borodulin
 */
class RefreshToken extends BaseModel
{
    private $_refreshToken;

    /**
     * Value MUST be set to "refresh_token".
     * @var string
     */
    public $grant_type;

    /**
     * The refresh token issued to the client.
     * @var string
     */
    public $refresh_token;

    /**
     * The scope of the access request as described by Section 3.3.
     * @var string
     */
    public $scope;
    /**
     *
     * @var string
     */
    public $client_id;
    /**
     *
     * @var string
     */
    public $client_secret;

    public function rules()
    {
        return [
            [['client_id', 'grant_type', 'client_secret', 'refresh_token'], 'required'],
            [['client_id', 'client_secret'], 'string', 'max' => 80],
            [['refresh_token'], 'string', 'max' => 40],
            [['client_id'], 'validateClientId'],
            [['client_secret'], 'validateClientSecret'],
            [['refresh_token'], 'validateRefreshToken'],
        ];
    }

    /**
     * @return array
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\db\StaleObjectException
     * @throws \yuncms\oauth2\Exception
     */
    public function getResponseData()
    {
        /** @var  \yuncms\oauth2\models\RefreshToken $refreshToken */
        $refreshToken = $this->getRefreshToken();

        /** @var AccessToken $accessToken */
        $accessToken = AccessToken::createAccessToken([
            'client_id' => $this->client_id,
            'user_id' => $refreshToken->user_id,
            'expires' => $this->accessTokenLifetime + time(),
            'scope' => $refreshToken->scope,
        ]);

        $refreshToken->delete();

        $refreshToken = \yuncms\oauth2\models\RefreshToken::createRefreshToken([
            'client_id' => $this->client_id,
            'user_id' => $refreshToken->user_id,
            'expires' => $this->refreshTokenLifetime + time(),
            'scope' => $refreshToken->scope,
        ]);

        return [
            'access_token' => $accessToken->access_token,
            'expires_in' => $this->accessTokenLifetime,
            'token_type' => $this->tokenType,
            'scope' => $refreshToken->scope,
            'refresh_token' => $refreshToken->refresh_token,
        ];
    }

    /**
     * @param $attribute
     * @param $params
     * @throws \yuncms\oauth2\Exception
     */
    public function validateRefreshToken($attribute, $params)
    {
        $this->getRefreshToken();
    }

    /**
     *
     * @return \yuncms\oauth2\models\RefreshToken
     * @throws \yuncms\oauth2\Exception
     */
    public function getRefreshToken()
    {
        if (is_null($this->_refreshToken)) {
            if (empty($this->refresh_token)) {
                $this->errorServer(Yii::t('oauth2', 'The request is missing "refresh_token" parameter'));
            }
            if (!$this->_refreshToken = \yuncms\oauth2\models\RefreshToken::findOne(['refresh_token' => $this->refresh_token])) {
                $this->errorServer(Yii::t('oauth2', 'The Refresh Token is invalid'));
            }
        }
        return $this->_refreshToken;
    }

    public function getRefresh_token()
    {
        return $this->getRequestValue('refresh_token');
    }
}
