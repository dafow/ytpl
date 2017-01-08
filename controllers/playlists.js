const request = require('request');

/**
 * GET /
 * Playlists index page.
 */
exports.index = (req, res) => {
  res.render('playlists', {
    title: 'Playlists'
  });
};

exports.create = (req, res) => {
	
};