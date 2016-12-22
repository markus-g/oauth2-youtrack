<?php

namespace MarkusG\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Youtrack
 * @package Markusg\OAuth2\Client\Provider
 */
class Youtrack extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     *
     */
    const URL_AUTHORIZE_PATH = '/hub/api/rest/oauth2/auth';
    /**
     *
     */
    const URL_ACCESS_TOKEN_PATH = '/hub/api/rest/oauth2/token';
    /**
     *
     */
    const URL_RESOURCE_OWNER_DETAILS_PATH = '/hub/api/rest/users/me';

    /**
     *
     */
    const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'id';

    /**
     * @var string
     */
    protected $urlAuthorize;
    /**
     * @var string
     */
    protected $urlAccessToken;
    /**
     * @var string
     */
    protected $urlResourceOwnerDetails;
    /**
     * @var
     */
    protected $scopes;
    /**
     * @var
     */
    protected $requestCredentials;
    /**
     * @var string If set, this will be sent to youtrack as the "$request_credentials" parameter.
     * @link https://www.jetbrains.com/help/hub/2.5/Authorization-Code.html#PreRequisites
     */
    protected $youtrackUrl;
    /**
     * @var string If set, this will be sent to youtrack as the "access_type" parameter.
     * @link https://www.jetbrains.com/help/hub/2.5/Authorization-Code.html#PreRequisites
     */
    protected $accessType;
    /**
     * @var
     */
    protected $scope;


    /**
     * Youtrack constructor.
     * @param array $options
     * @param array $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);
    }

    /**
     * Returns the base URL for authorizing a client.
     *
     * Eg. https://oauth.service.com/authorize
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->getYoutrackUrl() . self::URL_AUTHORIZE_PATH;
    }

    /**
     * @return string
     */
    public function getYoutrackUrl()
    {
        return rtrim($this->youtrackUrl, '/');
    }

    /**
     * Returns the base URL for requesting an access token.
     *
     * Eg. https://oauth.service.com/token
     *
     * @param array $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->getYoutrackUrl() . self::URL_ACCESS_TOKEN_PATH;
    }


    /**
     * Requests an access token using a specified grant and option set.
     *
     * @param  mixed $grant
     * @param  array $options
     * @return AccessToken
     */
    public function getAccessToken($grant, array $options = [])
    {
        $grant = $this->verifyGrant($grant);

        $params = [
            'redirect_uri' => $this->redirectUri,
            'headers' => ['Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)]
        ];
        $params = $grant->prepareRequestParameters($params, $options);
        $request = $this->getAccessTokenRequest($params);
        $response = $this->getResponse($request);
        $prepared = $this->prepareAccessTokenResponse($response);
        $token = $this->createAccessToken($prepared, $grant);

        return $token;
    }

    /**
     * Returns authorization parameters based on provided options.
     *
     * @param  array $options
     * @return array Authorization parameters
     */
    protected function getAuthorizationParameters(array $options)
    {
        if (isset($options['scope'])) {
            $this->scope = array_merge((array)$options['scope'], (array)$this->scope);
        }
        $options['scope'] = array_merge($this->getDefaultScopes(), (array)$this->scope);
        $params = array_merge(
            parent::getAuthorizationParameters($options),
            array_filter([
                'access_type' => $this->accessType,
                'request_credentials' => $this->requestCredentials,
            ])
        );
        return $params;
    }

    /**
     * Returns the default scopes used by this provider.
     *
     * This should only be the scopes that are required to request the details
     * of the resource owner, rather than all the available scopes.
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return ['0-0-0-0-0'];
    }

    /**
     * @param array $params
     * @return array
     */
    protected function getAccessTokenOptions(array $params)
    {
        $headers = [
            'content-type' => 'application/x-www-form-urlencoded'
        ];
        if (isset($params['headers'])) {
            $headers = array_merge($headers, $params['headers']);
        }
        $options = ['headers' => $headers];
        unset($params['headers']);

        if ($this->getAccessTokenMethod() === self::METHOD_POST) {
            $options['body'] = $this->getAccessTokenBody($params);
        }

        return $options;
    }

    /**
     * Checks a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  array|string $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['error'])) {
            $message = '';
            if (isset($data['error_developer_message'])) {
                $message = $data['error_developer_message'];
            } elseif (isset($data['error_description'])) {
                $message = $data['error_description'];
            }
            $message = $data['error'] . ': ' . $message;
            if (isset($data['error_uri'])) {
                $message .= ' Check ' . $data['error_uri'];
            }

            throw new IdentityProviderException($message, $data['error_code'], $data);
        }
    }

    /**
     * Generates a resource owner object from a successful resource owner
     * details request.
     *
     * @param  array $response
     * @param  AccessToken $token
     * @return ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new YoutrackUser($response, self::ACCESS_TOKEN_RESOURCE_OWNER_ID);
    }

    /**
     * Requests resource owner details.
     *
     * @param  AccessToken $token
     * @return mixed
     */
    protected function fetchResourceOwnerDetails(AccessToken $token)
    {
        $url = $this->getResourceOwnerDetailsUrl($token);

        $options = ['body' => \GuzzleHttp\Psr7\build_query(['scope' => $this->scopes])];
        $request = $this->getAuthenticatedRequest(self::METHOD_GET, $url, $token, $options);

        return $this->getResponse($request);
    }

    /**
     * Returns the URL for requesting the resource owner's details.
     *
     * @param AccessToken $token
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->getYoutrackUrl() . self::URL_RESOURCE_OWNER_DETAILS_PATH;
    }

    /**
     * Returns the default headers used by this provider.
     *
     * Typically this is used to set 'Accept' or 'Content-Type' headers.
     *
     * @return array
     */
    protected function getDefaultHeaders()
    {
        return ['Accept' => 'application/json'];
    }

    /**
     * Returns the string that should be used to separate scopes when building
     * the URL for requesting an access token.
     *
     * @return string Scope separator, defaults to ','
     */
    protected function getScopeSeparator()
    {
        return ' ';
    }
}