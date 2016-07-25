<?php
	error_reporting(E_ALL);
	ini_set('display_errors', 1);

	if (!defined('API')) {
		die('Direct access not permitted.');
	}
	require_once('secret.php');

	findGroupByName('test');

	function print_2($text) {
		echo "<pre>" . print_r($text, true) . "</pre>";
	}

	/**
	 * Attempts to find the group in which it has a given name
	 * @param $name The name that it's looking for
	 * @return the group id if successful, otherwise false
	 */
	function findGroupByName($name) {
		global $TOKEN;
		global $ME;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://api.groupme.com/v3/groups?token=${TOKEN}&per_page=100");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$contents = curl_exec($ch);
		curl_close($ch);

		$groups = json_decode($contents, true)['response'];
		foreach ($groups as $group) {
			foreach ($group['members'] as $member) {
				if ($member['user_id'] == $ME && $member['nickname'] == $name) {
					return $group['group_id'];
				}
			}
		}
		return false;
	}

	/**
	 * Attempts to find the group in which it has a given name
	 * @param $name The name that it's looking for
	 * @return the group id if successful, otherwise false
	 */
	function analyze($group) {
		global $TOKEN;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://api.groupme.com/v3/groups/${group}?token=${TOKEN}&per_page=100");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$contents = curl_exec($ch);
		curl_close($ch);

		$members = json_decode($contents, true)['response']['members'];
		$users = [];

		foreach ($members as $member) {
			$users[$member['user_id']] = [
				'name' => $member['nickname'],
				'total_likes_received' => 0,
				'total_likes_given' => 0,
				'total_number' => 0,
				'max_likes' => 0,
				'best_comment' => '',
			];
		}

		print_2($users);

		$messages = [];
		getMessages($group, $messages);
		print_2($messages);
	}

	function getMessages($group, &$messages, $before = null) {
		global $TOKEN;

		if ($before) {
			$url = "https://api.groupme.com/v3/groups/${group}/messages?token=${TOKEN}&limit=100&before_id=${before}";
		} else {
			$url = "https://api.groupme.com/v3/groups/${group}/messages?token=${TOKEN}&limit=100";
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$contents = json_decode(curl_exec($ch), true);
		curl_close($ch);

		if ($contents) {
			foreach ($contents['response']['messages'] as $msg) {
				$messages[] = [
					"attachments" => $msg["attachments"],
					"likes" => $msg["favorited_by"],
					"id" => $msg["id"],
					"sender_id" => $msg["sender_id"],
					"sender_type" => $msg["sender_type"],
					"text" => $msg["text"],
				];
			}

			getMessages($group, $messages, $messages[count($messages)-1]["id"]);
		} else {
			echo "Done!";
		}
	}
?>