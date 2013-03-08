<?php

use Nette\Application\UI,
	Nette\Security as NS;


class SignPresenter extends BasePresenter
{

	public function actionTwitterLogin()
	{
		$params = $this->getContext()->params;
		$session = $this->getSession('oauth');
		$oauth = new TwitterOAuth($params['twitterKey'], $params['twitterSecret']);

		$requestToken = $oauth->getRequestToken($this->link('//twitterLoginCallback'));

		$session->requestTokenKey = $requestToken['oauth_token'];
		$session->requestTokenSecret = $requestToken['oauth_token_secret'];

		$url = $oauth->getAuthorizeURL($requestToken['oauth_token']);
		$this->redirectUrl($url, 301);
	}


	public function actionTwitterLoginCallback()
	{
		if (!isset($this->params['oauth_verifier'])) {
			$this->flashMessage('Login unsuccessful');
			$this->redirect('Homepage:default');
		}

		$params = $this->getContext()->params;
		$session = $this->getSession('oauth');

		if (!isset ($session->requestTokenKey)) {
			$this->flashMessage('Login error');
			$this->redirect('Homepage:default');
		}

		$oauth = new TwitterOAuth($params['twitterKey'], $params['twitterSecret'], $session->requestTokenKey, $session->requestTokenSecret);
		unset($session->requestTokenKey, $session->requestTokenSecret);
		$info = $oauth->getAccessToken($this->params['oauth_verifier']);

		$loginData = (object) array(
			'type'              => 'twitter',
			'name'              => $info['screen_name'],
			'accessTokenKey'    => $info['oauth_token'],
			'accessTokenSecret' => $info['oauth_token_secret'],
		);

		try {
			$this->getUser()->setExpiration('+ 14 days', FALSE);
			$this->getUser()->login($loginData);
			$this->redirect('Homepage:default');

		} catch (NS\AuthenticationException $e) {
			$this->flashMessage($e->getMessage());
		}

		$this->flashMessage('uživatel přidán');
		$this->redirect('Homepage:default');
	}

}
