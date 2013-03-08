<?php


class Model extends Nette\Object
{

	function loginTwitterUser($data) {
		$user = $this->getUser($data->name);
		if (!$user) {
			unset($data->type);
			$this->addUser($data);
			$user = $this->getUser($data->name);
		}
		return $user;
	}


	private function addUser($data) {
		dibi::query('insert into autotw_users', (array) $data);
	}


	function getUser($name) {
		return dibi::fetch('select * from autotw_users where name = ?', $name);
	}


	function addTweets($user, array $tweets) {
		foreach ($tweets as &$t) {
			$t['userId'] = $user->id;
			$t['date'] = date("Y-m-d H:i:s", $t['date']);
		}

		if ($tweets)
			dibi::query('insert into autotw_tweets %ex', $tweets);
	}


	function getQueue($user) {
		return dibi::fetchAll('
			select *
			from autotw_tweets
			where userId = %i', $user->id, '
			and posted = false
			and deleted = false
			order by date
		');
	}


	function deleteTweet($user, $tweetId) {
		dibi::query('
			update autotw_tweets
			set deleted = true
			where id = %i', $tweetId, '
			and userId = %i', $user->id
		);
	}


	function getTweetsToSend() {
		return dibi::fetchAll('
			select t.*, u.name, u.accessTokenKey, u.accessTokenSecret
			from autotw_tweets t join autotw_users u on t.userId = u.id
			where t.posted = false and t.deleted = false and t.date < now()
			');
	}


	function markAsSent($tweetId) {
		dibi::query('update autotw_tweets set posted = 1 where id = %i', $tweetId);
	}

}
