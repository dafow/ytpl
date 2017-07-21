<?php
class Video extends Controller {

	function beforeRoute($f3) {
		if (!$f3->exists('SESSION.uid')) {
			$f3->reroute('/login', false);
		}
	}

	function update($f3) {
		$db = $this->db;
		$user = new DB\SQL\Mapper($db, 'users');
		$user->load(array('id=?', $f3->get('SESSION.uid')));
		if (!$user->dry()) {
			$videosMapper = new DB\SQL\Mapper($db, 'videos');
			$videosMapper->load(array('id=?', $f3->get('POST.videoId')));
			if (!$videosMapper->dry()) {
				
				//updates can be partial so build an array of updated fields to echo to whatever component is calling this
				$updates = array();
				if ($f3->exists('POST.title')) {
					$newTitle = $f3->get('POST.title');
					//check if the title isn't just the same and that the new title exists in the video's history
					if ($newTitle != $videosMapper->currentTitle && strpos($videosMapper->titleHistory, $newTitle) !== false) {
						$videosMapper->currentTitle = $f3->get('POST.title');
						$updates['title'] = true;
					}
				}
				
				if ($f3->exists('POST.owTitle')) {
					$videosMapper->forceTitleOverwrite = "0";
				}
				
				$videosMapper->save();
				echo json_encode($updates);
			}
		}
	}
	
	function delete($f3, $params) {
		//check if user exists and is logged in
		$db = $this->db;
		$user = new DB\SQL\Mapper($db, 'users');
		$user->load(array('id=?', $f3->get('SESSION.uid')));
		if (!$user->dry()) {
		
			//check the sent params are OK
			if (isset($params['id']) && !empty($params['id']) && $f3->exists('POST.plid') && !empty($f3->get('POST.plid'))) {
				$playlistsMapper = new DB\SQL\Mapper($db, 'playlists');
				$playlistsMapper->load(array('id=?', $f3->get('POST.plid')));
				
				//check if the playlist is in the user's collection
				if (!$playlistsMapper->dry() && !is_null($user->playlists) && in_array($playlistsMapper->id, explode(",", $user->playlists))) {
					$videosMapper = new DB\SQL\Mapper($db, 'videos');
					$videosMapper->load(array('id=?', $params['id']));
					
					//check if the video is in the playlist's collection and remove it if it is
					if (!$videosMapper->dry() && !is_null($playlistsMapper->videos)) {
						$videos = explode(",", $playlistsMapper->videos);
						$video = array_search($videosMapper->id, $videos);
						if ($video !== false) {
							$videosMapper->erase();
							unset($videos[$video]);
							$playlistsMapper->videos = implode(",", $videos);
							$playlistsMapper->save();
						}
					}
				}
			}
		}
	}
}
?>