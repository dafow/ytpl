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
				echo json_encode($f3->get('POST'));
			}
		}
	}
}
?>