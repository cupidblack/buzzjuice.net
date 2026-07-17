<?php

require_once __DIR__ . '/../GitHubClient.php';
require_once __DIR__ . '/../GitHubService.php';
require_once __DIR__ . '/../objects/GitHubHook.php';
	

class GitHubReposHooks extends GitHubService {


	/**
	 * List
	 * 
	 * @return GitHubHook
	 */
	public function listReposHooks( $owner, $repo, $id ) {
		$data = array();
		
		return $this->client->request("/repos/$owner/$repo/hooks/$id", 'GET', $data, 200, 'GitHubHook');
	}
	
	/**
	 * Create a hook
	 * 
	 */
	
	public function createHook( $owner, $repo, $events = array(), $url ) {
	$data=  array(
		'name' => 'web',
		'active' => true,
		'events' => $events,
		'config' => 
		array(
			'url' => $url,
			'content_type' => 'form',
			'insecure_ssl' => '0',
		),
	);  
		$data=  json_encode($data);
		return $this->client->request("/repos/$owner/$repo/hooks", 'POST', $data, 201, 'string');
	}
	public function deleteHook( $owner, $repo, $id ) {
		$data = array();
		
		return $this->client->request("/repos/$owner/$repo/hooks/$id", 'DELETE', $data, 204, 'string');
	}
}
