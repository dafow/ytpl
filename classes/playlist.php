<?php
class Playlist extends Controller {

	//Show a collection of user playlists
	function index($f3) {
		$db = $this->db;
		$user = new DB\SQL\Mapper($db, 'users');
		$user->load(array('id=?', $f3->get('SESSION.uid')));
		if ($user->dry()) {
			//shouldn't happen since user has to be logged in ?
			$f3->push('flash', (object)array(
				'lvl'	=>	'danger',
				'msg'	=>	'An error occurred while retrieving your playlists'));
		}
		else {
			if (!is_null($user->playlists)) {
				$playlists = $db->exec("SELECT * FROM playlists WHERE id IN ($user->playlists)");
				$f3->set('playlists', $playlists);
			}
		}
		
		echo \Template::instance()->render('playlists.htm');
	}
	
	//Show a collection of a playlist's videos
	function show($f3, $params) {
		$plid = $params['plid'];
		$db = $this->db;
		$playlist = new DB\SQL\Mapper($db, 'playlists');
		$usersMapper = new DB\SQL\Mapper($db, 'users');
		$usersMapper->load(array('id=?', $f3->get('SESSION.uid')));
		if (!$usersMapper->dry() && !is_null($usersMapper->playlists)) {
		
			//check if user has that playlist in his collection
			$userPlaylists = explode(",", $usersMapper->playlists);
			if (in_array($plid, $userPlaylists)) {
				$playlist->load(array('id=?', $plid));
				if (!$playlist->dry() && !is_null($playlist->videos)) {
					$videos = $db->exec("SELECT * FROM videos WHERE id IN ($playlist->videos)");
					$f3->set('videos', $videos);
				}
			}
			else {
				$f3->push('flash', (object)array(
					'lvl'	=>	'warning',
					'msg'	=>	'Playlist not found'));
			}
		}
		
		$f3->set('plid', $plid);
		echo \Template::instance()->render('playlist.htm');
	}
	
	//Add a playlist to the user's playlist collection
	function addPlaylist($f3) {
		$db = $this->db;
		$user = new DB\SQL\Mapper($db, 'users');
		$user->load(array('id=?', $f3->get('SESSION.uid')));
		
		if (!$user->dry()) {
			//check if playlist is accessible
			require_once dirname(__FILE__) . '/ext/youtubeapi.php';
			$ytapi = new Youtubeapi($f3);
			$playlist = $ytapi->getPlaylist($f3->get('POST.ytid'));
			
			//check if playlist exists and is not empty from info retrieved from youtube api
			if (!is_null($playlist) && $playlist['pageInfo']['totalResults'] != 0) {
				//check if the playlist isn't already in the user playlists field
				$doNothing = false;
				if (!is_null($user->playlists)) {
					$userPlaylists = $this->db->exec("SELECT * FROM playlists WHERE id IN ($user->playlists)");
					foreach($userPlaylists as $userPlaylist) {
						if ($userPlaylist['ytid'] == $playlist['items'][0]['id']) {
							$doNothing = true;
							$f3->push('flash', (object)array(
								'lvl'	=>	'warning',
								'msg'	=>	'This playlist is already in your collection'));
							break;
						}
					}
				}
				
				if (!$doNothing) {
					//create playlist row
					$playlistsMapper = new DB\SQL\Mapper($db, 'playlists');
					$playlistsMapper->ytid = $playlist['items'][0]['id'];
					$playlistsMapper->name = $playlist['items'][0]['snippet']['title'];
					$playlistsMapper->thumbnails = $playlist['items'][0]['snippet']['thumbnails']['high']['url'];
					$playlistsMapper->status = $playlist['items'][0]['status']['privacyStatus'];
					$playlistsMapper->save();
					
					if (is_null($user->playlists) || empty($user->playlists)) {
						$user->playlists = $playlistsMapper->id;
					}
					else {
						$user->playlists .= ',' . $playlistsMapper->id;
					}
					$user->save();
					
					$f3->push('flash', (object)array(
						'lvl'	=>	'success',
						'msg'	=>	'Playlist successfully added'));
				}
			}
			else {
				$f3->push('flash', (object)array(
					'lvl'	=>	'warning',
					'msg'	=>	'Sorry, couldn\'t find that playlist'));
			}
		}
		
		$this->index($f3);
	}
	
	//Sync current playlist with Youtube's
	function sync($f3) {
		$plid = $f3->get('POST.plid');
		$db = $this->db;
		$user = new DB\SQL\Mapper($db, 'users');
		$user->load(array('id=?', $f3->get('SESSION.uid')));
		
		if (!$user->dry() && !is_null($user->playlists)) {
			//check user has playlist in his collection
			$userPlaylists = $db->exec("SELECT * FROM playlists WHERE id IN ($user->playlists)");
			$doNothing = true;
			foreach($userPlaylists as $userPlaylist) {
				if ($userPlaylist['id'] == $plid) {
					$doNothing = false;
					break;
				}
			}
			
			if (!$doNothing) {
				//get db playlist and videos collection
				$playlistsMapper = new DB\SQL\Mapper($db, 'playlists');
				$playlistsMapper->load(array('id=?', $plid));
				
				if (!$playlistsMapper->dry()) {
					$dbVideos = $db->exec("SELECT * FROM videos WHERE id IN ($playlistsMapper->videos)");
					
					//get youtube playlist items
					require_once dirname(__FILE__) . '/ext/youtubeapi.php';
					$ytapi = new Youtubeapi($f3);
					$ytVideos = $ytapi->getPlaylistItems($playlistsMapper->ytid);
					
					if (!is_null($ytVideos) && $dbVideos !== false) {
						//if playlist contains more than maxResults items, get next pages videos
						$nextPageToken = isset($ytVideos['nextPageToken']) ? $ytVideos['nextPageToken'] : false;
						while($nextPageToken) {
							$nextVideos = $ytapi->getPlaylistItems($playlistsMapper->ytid, $nextPageToken);
							if (!is_null($nextVideos)) {
								$ytVideos['items'] = array_merge($ytVideos['items'], $nextVideos['items']);
								$nextPageToken = isset($nextVideos['nextPageToken']) ? $nextVideos['nextPageToken'] : false;
							}
							else {
								$f3->push('flash', (object)array(
									'lvl'	=>	'warning',
									'msg'	=>	'Error(s) occurred while synchronizing some of your playlist\'s items'));
								break;
							}
						}
						
						//compare both playlists collections
						$videosMapper = new DB\SQL\Mapper($db, 'videos');
						foreach($ytVideos['items'] as $ytVideo) {
							//echo "<pre>";var_dump($ytVideo);echo "</pre>";
							$dbVideoKey = array_search($ytVideo['snippet']['resourceId']['videoId'], array_column($dbVideos, 'ytid'));
							if ($dbVideoKey !== false) {
								$dbVideo = $dbVideos[$dbVideoKey];
								
								$videosMapper->reset();
								$videosMapper->load(array('id=?', $dbVideos['id']));
								//compare title
								if ($dbVideo['currentTitle'] != $ytVideo['snippet']['title']) {
									if (!is_null($videosMapper->history) || empty($videosMapper->history)) {
										$videosMapper->history = $dbVideo['currentTitle'];
									}
									else {
										$titleHistory = explode("");
									}
								}
							}
						}
					}
					else {
						$f3->push('flash', (object)array(
							'lvl'	=>	'danger',
							'msg'	=>	'An error occurred while synchronizing your playlist'));
					}
				}
				else {
					$f3->push('flash', (object)array(
						'lvl'	=>	'danger',
						'msg'	=>	'An error occurred while synchronizing your playlist'));
				}
			}
		}
		
		$this->index($f3);
	}
}
?>