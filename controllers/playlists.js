/**
 * GET /
 * Playlists index page.
 */
exports.index = (req, res) => {
  res.render('playlists/index', {
    title: 'Playlists'
  });
};

