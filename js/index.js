
$(function() {
  $('#sidebar a').click(function(e) {
    if (e.currentTarget.href.indexOf('/?') == -1) {
      return;
    }

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


  $('#server').change(function(e) {
    if (location.href.indexOf('?') == -1) {
      location.href = location.href+'?s='+e.target.value;
    } else if (location.href.indexOf('&s=') == -1) {
      location.href = location.href+'&s='+e.target.value;
    } else {
      location.href = location.href.replace(/s=[0-9]*/, 's='+e.target.value);
    }
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

