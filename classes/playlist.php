<?php
class Playlist extends Controller {

	//Show a collection of user playlists
	function index($f3, $params) {
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
				$playlistsMapper = new DB\SQL\Mapper($db, 'playlists');
				$limit = isset($_GET['mode']) ? 5 : 10;
				$page = isset($params['page']) ? $params['page'] - 1 : 0;
				$playlists = $playlistsMapper->paginate($page, $limit, "id IN ($user->playlists)");
				if (!is_null($playlists['pos'])) {
					$f3->set('currentPage', $playlists['pos']);
					$f3->set('pagesCount', $playlists['count']);
					$f3->set('playlists', $playlists['subset']);
				}
				else {
					$f3->push('warning', (object)array(
						'lvl'	=>	'warning',
						'msg'	=>	'Not much to see...'));
					$f3->set('playlists', array());
				}
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
					if (isset($_GET['orderBy']) && isset($_GET['order'])) {
						if ($_GET['orderBy'] == 'publishedAt') {
							$videos = $_GET['order'] === 'ASC' ?
										$db->exec("SELECT * FROM videos WHERE id IN ($playlist->videos) ORDER BY publishedAt ASC") :
										$db->exec("SELECT * FROM videos WHERE id IN ($playlist->videos) ORDER BY publishedAt DESC");
						}
					}
					else {
						$videos = $db->exec("SELECT * FROM videos WHERE id IN ($playlist->videos)");
					}
					
					$f3->set('videos', $videos);
					$f3->set('playlistTitle', $playlist->name);
				}
				elseif (!$playlist->dry() && is_null($playlist->videos)) {
					$f3->set('videos', array());
					$f3->set('playlistTitle', $playlist->name);
				}
			}
			else {
				$f3->push('flash', (object)array(
					'lvl'	=>	'warning',
					'msg'	=>	'Playlist not found'));
			}
		}
		
		$f3->set('plid', $plid);
		echo View::instance()->render('playlist.htm');
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
					//check if last sync was more than 10mins ago
					if (!(!is_null($playlistsMapper->lastSync) && $playlistsMapper->lastSync > date('Y-m-d H:i:s', mktime(date('H'), date('i') - 5)))
						|| is_null($playlistsMapper->lastSync)) {
						$dbVideos = is_null($playlistsMapper->videos) ?
									array() : $db->exec("SELECT * FROM videos WHERE id IN ($playlistsMapper->videos)");
						
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
								//find corresponding video in the database
								$dbVideoKey = array_search($ytVideo['snippet']['resourceId']['videoId'], array_column($dbVideos, 'ytid'));
								if ($dbVideoKey !== false) {
									$dbVideo = $dbVideos[$dbVideoKey];
									
									$videosMapper->reset();
									$videosMapper->load(array('id=?', $dbVideo['id']));
									
									if (!$videosMapper->dry()) {
										//compare titles
										if ($dbVideo['currentTitle'] != $ytVideo['snippet']['title']) {
											//if they are different, add the name to the video's name history
											$videosMapper->titleHistory = $this->addToCsvUnique($ytVideo['snippet']['title'], $videosMapper->titleHistory);
											
											if ($videosMapper->forceTitleOverwrite == 1) {
												$videosMapper->currentTitle = $ytVideo['snippet']['title'];
											}
										}
										
										$videosMapper->status = $ytVideo['status']['privacyStatus'];
										if (isset($ytVideo['snippet']['thumbnails'])) {
											$videosMapper->thumbnails = $ytVideo['snippet']['thumbnails']['high']['url'];
										}
										
										$videosMapper->save();
									}
								}
								
								else {
									//add the video to the database and to the user's playlist
									$videosMapper->reset();
									$videosMapper->ytid = $ytVideo['snippet']['resourceId']['videoId'];
									$videosMapper->currentTitle = str_replace(";", ",", $ytVideo['snippet']['title']);
									$videosMapper->titleHistory = $videosMapper->currentTitle;
									$videosMapper->thumbnails = isset($ytVideo['snippet']['thumbnails']) ? 
																$ytVideo['snippet']['thumbnails']['high']['url'] : null;
									$videosMapper->publishedAt = $ytVideo['snippet']['publishedAt'];
									$videosMapper->status = $ytVideo['status']['privacyStatus'];
									$videosMapper->save();
									
									if (is_null($playlistsMapper->videos)) {
										$playlistsMapper->videos = $videosMapper->id;
									}
									else {
										$playlistsMapper->videos .= "," . $videosMapper->id;
									}
									$playlistsMapper->save();
								}
							}
							
							$playlistsMapper->load(array('id=?', $plid));
							$playlistsMapper->lastSync = date("Y-m-d H:i:s");
							$playlistsMapper->save();
							
							$f3->push('flash', (object)array(
								'lvl'	=>	'success',
								'msg'	=>	'Sync complete'));
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
							'msg'	=>	'In order to void excessive bandwidth usage, please wait at least 5 minutes before syncing again'));
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
		
		$this->show($f3, array('plid' => $plid));
	}
}
?>