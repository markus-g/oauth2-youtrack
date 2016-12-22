# YouTrack Provider for OAuth 2.0 Client

This package provides YouTrack OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require markus-g/oauth2-youtrack
```

## Usage

Usage is the same as The League's OAuth client, using `MarkusG\OAuth2\Client\Provider\Youtrack` as the provider.

### Authorization Code Flow
See https://www.jetbrains.com/help/hub/2.5/Authorization-Code.html for more information.

```php
$provider = new \MarkusG\OAuth2\Client\Provider\Youtrack([
    'clientId' => 'YOUR_CLIENT_ID',
    'clientSecret' => 'YOUR_CLIENT_SECRET',   
    'youtrackUrl' => 'http://your-youtrack-url.com',   
    'redirectUri' => 'https://example.com/callback-url',
    'scope' => 'SCOPE', //use YouTrack service ID.
    'requestCredentials' => 'skip', // (optional)
]);


// If we don't have an authorization code then get one
if (!isset($_GET['code'])) {

    // Fetch the authorization URL from the provider; this returns the
    // urlAuthorize option and generates and applies any necessary parameters
    // (e.g. state).
    $authorizationUrl = $provider->getAuthorizationUrl();

    // Get the state generated for you and store it to the session.
    $_SESSION['oauth2state'] = $provider->getState();

    // Redirect the user to the authorization URL.
    header('Location: ' . $authorizationUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    exit('Invalid state');
} else {
    try {
        // Try to get an access token using the authorization code grant.
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        // We have an access token, which we may use in authenticated
        // requests against the service provider's API.
        echo $accessToken->getToken() . "\n";
        echo $accessToken->getRefreshToken() . "\n";
        echo $accessToken->getExpires() . "\n";
        echo ($accessToken->hasExpired() ? 'expired' : 'not expired') . "\n";

        // Using the access token, we may look up details about the
        // resource owner.
        $resourceOwner = $provider->getResourceOwner($accessToken);
        
        var_dump($resourceOwner->getCreationTime());
        var_dump($resourceOwner->getLastAccessTime());
        var_dump($resourceOwner->toArray());
        
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        // Failed to get the access token or user details.
        exit($e->getMessage());
    }
}
```

### Client Credentials Grant
See https://www.jetbrains.com/help/hub/2.5/Client-Credentials.html for more information.

When your application is acting on its own behalf to access resources it controls/owns in a service provider, it may use the client credentials grant type. This is best used when the credentials for your application are stored privately and never exposed (e.g. through the web browser, etc.) to end-users. This grant type functions similarly to the resource owner password credentials grant type, but it does not request a user's username or password. It uses only the client ID and secret issued to your client by the service provider.

Unlike earlier examples, the following does not work against a functioning demo service provider. It is provided for the sake of example only.

``` php
// Note: the GenericProvider requires the `urlAuthorize` option, even though
// it's not used in the OAuth 2.0 client credentials grant type.

$provider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId' => 'YOUR_CLIENT_ID',
    'clientSecret' => 'YOUR_CLIENT_SECRET',   
    'youtrackUrl' => 'http://your-youtrack-url.com',   
    'redirectUri' => 'https://example.com/callback-url',
]);

try {

    // Try to get an access token using the client credentials grant.
    // Use the YouTrack ID under service in YouTrack HUB for the scope parameter
     $accessToken = $provider->getAccessToken('client_credentials', ['scope' => 'SCOPE']);

} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

    // Failed to get the access token
    exit($e->getMessage());

}
```

### Refreshing A Token

Refresh tokens are only provided to applications which request offline access. You can specify offline access by setting the `accessType` option in your provider:

```php
$provider = new MarkusG\OAuth2\Client\Provider\Youtrack([
    'clientId' => 'YOUR_CLIENT_ID',
    'clientSecret' => 'YOUR_CLIENT_SECRET',
    'redirectUri' => 'http://your-redirect-uri',
    'accessType'   => 'offline',
]);
```

It is important to note that the refresh token is only returned on the first request after this it will be `null`. You should securely store the refresh token when it is returned:

```php
$grant = new \League\OAuth2\Client\Grant\RefreshToken();
$token = $provider->getAccessToken($grant, ['refresh_token' => $refreshToken]);
```
