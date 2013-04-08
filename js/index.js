$(function () {
    $('#sidebar a').click(function (e) {
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


    $('#server').change(function (e) {
        if (location.href.indexOf('?') == -1) {
            location.href = location.href + '?s=' + e.target.value;
        } else if (location.href.indexOf('&s=') == -1) {
            location.href = location.href + '&s=' + e.target.value;
        } else {
            location.href = location.href.replace(/s=[0-9]*/, 's=' + e.target.value);
        }
    });


    $('li.current').parents('li.folder').removeClass('collapsed');

    $('ul.keysContainer').on('click', 'li.folder', function (e) {
        var t = $(this);

        if ((e.pageY >= t.offset().top) &&
            (e.pageY <= t.offset().top + t.children('div').height())) {
            e.stopPropagation();
            t.toggleClass('collapsed');

            var keyName = $(this).find('a.deltree').attr('href').replace('delete.php?s=0&tree=', '');

            if (!t.hasClass('collapsed') && !t.hasClass('loaded')) {
                $.get('/loader.php', {action: 'lazyLoadKeys', key: keyName}).done(function (data) {
                    $(t).replaceWith(data);
                });
            }
        }


    });

    $('a').click(function () {
        $('li.current').removeClass('current');
    });

    $('li a').click(function () {
        $(this).parent().addClass('current');
    });


    $('#filter').focus(function () {
        if ($(this).hasClass('info')) {
            $(this).removeClass('info').val('');
        }
    }).keyup(function () {
            var val = $(this).val();

            $('li:not(.folder)').each(function (i, el) {
                var key = $('a', el).get(0);
                var key = unescape(key.href.substr(key.href.indexOf('key=') + 4));

                if (key.indexOf(val) == -1) {
                    $(el).addClass('hidden');
                } else {
                    $(el).removeClass('hidden');
                }
            });

            $('li.folder').each(function (i, el) {
                if ($('li:not(.hidden, .folder)', el).length == 0) {
                    $(el).addClass('hidden');
                } else {
                    $(el).removeClass('hidden');
                }
            });
        });

    $('.deltree').click(function (e) {
        e.preventDefault();

        if (confirm('Are you sure you want to delete this whole tree and all it\'s keys?')) {
            $.ajax({
                type: "POST",
                url: this.href,
                data: 'post=1',
                success: function (url) {
                    top.location.href = top.location.pathname + url;
                }
            });
        }
    });
});

