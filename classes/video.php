<?php
class Video extends Controller {
	function update($f3) {
		$db = $this->db;
		$user = new DB\SQL\Mapper($db, 'users');
		$user->load(array('id=?', $f3->get('SESSION.uid')));
		if (!$user->dry()) {
			$videosMapper = new DB\SQL\Mapper($db, 'videos');
			$videosMapper->load(array('id=?', $f3->get('POST.videoId')));
			if (!$videosMapper->dry()) {
			
				$updates = array();
				if ($f3->exists('POST.title')) {
					$newTitle = $f3->get('POST.title');
					if ($newTitle != $videosMapper->currentTitle && strpos($videosMapper->titleHistory, $newTitle) !== false) {
						$videosMapper->currentTitle = $f3->get('POST.title');
						$updates['title'] = true;
					}
				}
				
				$videosMapper->save();
				echo json_encode($updates);
			}
		}
	}
}
?>