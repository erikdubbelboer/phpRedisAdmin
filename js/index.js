
$(function() {
  $('#sidebar a').click(function(e) {
    e.preventDefault();

    var href;

    if ((e.currentTarget.href.indexOf('?') == -1) ||
        (e.currentTarget.href.indexOf('?') == (e.currentTarget.href.length - 1))) {
      href = 'overview.php';
    } else {
      href = e.currentTarget.href.substr(e.currentTarget.href.indexOf('?') + 1);

      if (href.indexOf('&') != -1) {
        href = href.replace('&', '.php?');
      } else {
        href += '.php';
      }
    }

    $('#iframe').attr('src', href);
  });


  $('li.current').parents('li.folder').removeClass('collapsed');

  $('li.folder').click(function(e) {
    var t = $(this);

    if ((e.pageY >= t.offset().top) &&
        (e.pageY <= t.offset().top + t.children('div').height())) {
      e.stopPropagation();
      t.toggleClass('collapsed');
    }
  });

  $('a').click(function() {
    $('li.current').removeClass('current');
  });

  $('li a').click(function() {
    $(this).parent().addClass('current');
  });
});

