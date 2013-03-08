<?php

use Nette\Security;


class Authenticator extends Nette\Object implements Security\IAuthenticator
{
	private $model;


	public function __construct(Model $model)
	{
		$this->model = $model;
	}


	/**
	 * Performs an authentication
	 * @param  array
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		$credentials = reset($credentials);

		if ($credentials->type === 'twitter') {
			$user = $this->model->loginTwitterUser($credentials);
			return new Security\Identity($user['id'], 'user', $user);

		} else {
			throw new Security\AuthenticationException("Unsupported authentication method", self::IDENTITY_NOT_FOUND);
		}
	}

}
