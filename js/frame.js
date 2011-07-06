
$(function() {
  if (history.replaceState) {
    window.parent.history.replaceState({}, '', document.location.href.replace('?', '&').replace(/([a-z]*)\.php/, '?$1'));
  }


  $('#type').change(function(e) {
    $('#hkeyp' ).css('display', e.target.value == 'hash' ? 'block' : 'none');
    $('#indexp').css('display', e.target.value == 'list' ? 'block' : 'none');
    $('#scorep').css('display', e.target.value == 'zset' ? 'block' : 'none');
  }).change();


  $('.delkey').click(function(e) {
    if (!confirm('Are you sure you want to delete this key and all it\'s values?')) {
      e.preventDefault();
    }
  });

  $('.delval').click(function(e) {
    if (!confirm('Are you sure you want to delete this value?')) {
      e.preventDefault();
    }
  });
});

