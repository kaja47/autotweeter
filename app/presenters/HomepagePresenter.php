<?php

use Nette\Application\UI;
use Nette\Diagnostics\Debugger;

class HomepagePresenter extends BasePresenter
{

	function startup()
	{
		parent::startup();
	}


	function getModel()
	{
		return $this->getService('model');
	}


	function actionLogout()
	{
		$this->getUser()->logout();
		$this->flashMessage('You have been signed out.');
		$this->redirect('default');
	}


	function actionDefault()
	{
		$this->sendTweets();

		if ($this->user->isLoggedIn()) {
			$this->template->queue = $this->model->getQueue($this->user);
		}
	}


	function getTwitter($accessTokenKey, $accessTokenSecret)
	{
		$params = $this->context->params;
		$ident = $this->user->identity;
		return new TwitterOAuth($params['twitterKey'], $params['twitterSecret'], $accessTokenKey, $accessTokenSecret);
	}


	function sendTweets()
	{
		foreach ($this->model->getTweetsToSend() as $tw) {
			$client = $this->getTwitter($tw->accessTokenKey, $tw->accessTokenSecret);
			$resp = $client->post('https://api.twitter.com/1/statuses/update.json', array(
				'status' => $tw->text,
			));
			if (isset($resp->error)) {
				Debugger::log("$resp->error (user: $tw->name, id: $tw->id, text: '$tw->text')", Debugger::ERROR);
			}
			$this->model->markAsSent($tw->id);
		}
	}


	function createComponentAddTweets()
	{
		$form = new UI\Form;
		$form->addTextArea('tweets', 'tweety');
		$form->addSubmit('send', 'přidat tweety do fronty');

		$err = $this->getSession('errors')->err;
		$form->setDefaults(array(
			'tweets' => $err ? join("\n", $err) : ""
		));

		$self = $this;
		$form->onSuccess[] = function ($form) use($self) {
			$lines = array_filter(array_map('trim', explode("\n", trim($form->values->tweets))));
			$err = $ok = array();
			$inPast = $tooLong = false;
			foreach ($lines as $l) {
				$segments = array();
				$offset = 0;
				while (($pos = strpos($l, ' ', $offset)) !== false) {
					$dateSeg = substr($l, 0, $pos);
					$textSeg = substr($l, $pos+1);
					$segments[$textSeg] = $dateSeg;
					$offset = $pos + 1;
				};

				$dates = array_filter(array_map('strtotime', $segments));
				if (!empty($dates)) {
					$date = end($dates);
					$text = key($dates);
					if ($date <= time()) {
						$err[] = $l;
						$inPast = true;
					} else if (mb_strlen($text) > 140) {
						$err[] = $l;
						$tooLong = true;
					} else {
						$ok[] = array('date' => $date, 'text' => $text);
					}
				} else {
					$err[] = $l;
				}
			}
			$sess = $self->getSession('errors')->err = $err;

			$self->model->addTweets($self->user, $ok);

			if (!empty($err))
				$self->flashMessage('Prosím opravte chybný formát tweetů.');
			if ($inPast)
				$self->flashMessage('Prosím neplánujte tweety do minulosti.');
			if ($tooLong)
				$self->flashMessage('Zkraťte tweety');

			$self->redirect('this');
		};

		return $form;
	}


	function actionDeleteTweet($tweetId)
	{
		$this->model->deleteTweet($this->user, $tweetId);
		$this->redirect('default');
	}

}
