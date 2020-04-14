<?php
class Youtubeapi {
	protected $apikey;

	function __construct($f3) {
		$this->apikey = $f3->get('youtubeapikey');
	}
	
	function getPlaylist($ytid) {
		$web = \Web::instance();
		$res = $web->request('https://www.googleapis.com/youtube/v3/playlists?' .
			http_build_query(
				array(
					'part'		=>	'contentDetails,status,snippet',
					'id'		=>	$ytid,
					'key'		=>	$this->apikey,
					'fields'	=>	'items(id,snippet(thumbnails/high,title),status),pageInfo/totalResults'
				)
			)
		);
		
		$json = json_decode($res['body'], true);
		//echo "<pre>";echo print_r($json);echo "</pre>";
		if (json_last_error() === JSON_ERROR_NONE && strpos($res['headers'][0], "200 OK") !== false) {
			return $json;
		}
		else {
			return null;
		}
	}
	
	function getPlaylistItems($ytid, $nextPageToken = null) {
		$web = \Web::instance();
		$res = $web->request('https://www.googleapis.com/youtube/v3/playlistItems?' .
			http_build_query(is_null($nextPageToken) ?
				array(
					'part'			=>	'status,snippet',
					'playlistId'	=>	$ytid,
					'maxResults'	=>	2,
					'key'			=>	$this->apikey,
					'fields'		=>	'items(snippet(publishedAt,resourceId/videoId,thumbnails/high/url,title),status),nextPageToken'
				) :
				array (
					'part'			=>	'status,snippet',
					'playlistId'	=>	$ytid,
					'maxResults'	=>	2,
					'pageToken'	=>	$nextPageToken,
					'key'			=>	$this->apikey,
					'fields'		=>	'items(snippet(publishedAt,resourceId/videoId,thumbnails/high/url,title),status),nextPageToken'
				)
			)
		);
		
		$json = json_decode($res['body'], true);
		// echo "<pre>";var_dump($res);echo "</pre>";
		if (json_last_error() === JSON_ERROR_NONE && strpos($res['headers'][0], "200 OK") !== false) {
			return $json;
		}
		else {
			return null;
		}
	}
}
?>