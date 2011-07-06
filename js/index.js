
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

  $('li.folder div').css('cursor', 'pointer').click(function() {
    $(this).parent().toggleClass('collapsed');
  });

  $('a').click(function() {
    $('li.current').removeClass('current');
  });

  $('li a').click(function() {
    $(this).parent().addClass('current');
  });
});

