<?php

namespace MarkusG\OAuth2\Client\Provider;

use DateTime;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

/**
 * Class YoutrackUser
 * @package Markusg\OAuth2\Client\Provider
 */
class YoutrackUser implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;
    /**
     * @var array
     */
    protected $response;
    /**
     * @var AccessToken
     */
    protected $token;

    /**
     * YoutrackUser constructor.
     * @param array $response
     * @param AccessToken $token
     */
    public function __construct(array $response, AccessToken $token)
    {
        $this->response = $response;
        $this->token = $token;
    }

    /**
     * @return array
     */
    public function getAvatar()
    {
        return $this->response['avatar'];
    }

    /**
     * @return \DateTime
     */
    public function getCreationTime()
    {
        return DateTime::createFromFormat('U.u', $this->response['creationTime'] / 1000);
    }

    /**
     * @return array
     */
    public function getDetails()
    {
        return $this->response['details'];
    }

    /**
     * @return array
     */
    public function getGroups()
    {
        return $this->response['groups'];
    }

    /**
     * @return boolean
     */
    public function isGuest()
    {
        return $this->response['guest'];
    }

    /**
     * @return \DateTime
     */
    public function getLastAccessTime()
    {
        return DateTime::createFromFormat('U.u', $this->response['lastAccessTime'] / 1000);
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->response['login'];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->response['name'];
    }

    /**
     * @return array
     */
    public function getProfile()
    {
        return $this->response['profile'];
    }

    /**
     * @return array
     */
    public function getProjectRoles()
    {
        return $this->response['projectRoles'];
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->response['roles'];
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->response['type'];
    }

    /**
     * @return array
     */
    public function getVcsUserNames()
    {
        return $this->response['VCSUserNames'];
    }

    /**
     * Returns the identifier of the authorized resource owner.
     *
     * @return string
     */
    public function getId()
    {
        return $this->response['id'];
    }

    /**
     * @return AccessToken
     */
    public function getToken(): AccessToken
    {
        return $this->token;
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}