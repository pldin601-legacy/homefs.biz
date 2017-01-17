var playingPage = false;

function doc_resize() {
    /* Search input */
    var padding = $(".nav-search-input").outerWidth(true) - $(".nav-search-input").width() + 8;
    $(".nav-search-input").width($(".nav-search").innerWidth() - padding);
    $(".nav-search-popup").width($(".nav-search").innerWidth() - padding);
    /* Navigation section */
    //$(".hfs-location").scrollLeft($(".loc-wrap").outerWidth(true));
}

function reset_page() {
    $("div.hfs-filelist").html('');
    $("div.hfs-smartloader").html('');
    $("div.hfs-information span").html('');
    homefs_variables.dirs = [];
    homefs_variables.files = [];
    homefs_variables.audio = [];
    homefs_popup_hide();
    hideInfromationBlock();
}

function draw_navigation_path(path) {
    $("span.loc-wrap").html('');
    for (var i in path)
        if (i == 0)
            $('#navBarCurrentTmpl').tmpl(path[i]).appendTo("span.loc-wrap");
        else
            $('#navBarTmpl').tmpl(path[i]).appendTo("span.loc-wrap");
}

function hide_audio_player() {
    $(".hfs-filelist-wrap").css({'padding-bottom': '0px'})
    $(".hfs-player-new").stop().animate({bottom: '-50px'}, 250, function() {
        $(this).css({display: 'none'});
    });
}

function show_audio_player() {
    $(".hfs-player-new").stop().animate({bottom: '0px'}, 250).css({display: 'table'});
    $(".hfs-footer-wrap").css({'padding-bottom': $(".hfs-player-new").outerHeight(true) + 'px'})
}

function draw_files_on_page(files, meta, showpath) {

    for (var i in files) {
        var tmp = files[i];
        var metadata = meta[tmp['id']];

        tmp['relative'] = date_delta_format(tmp['modified']);
        tmp['humanized'] = file_size_humanize(tmp['size']);

        if (tmp['type'] == 'audio') {
            if (metadata !== undefined)
                tmp['metadata'] = metadata;
            $('#fileListAudioTmpl').tmpl(tmp).appendTo("div.hfs-filelist");
            $('#fileItemInputAudioTmpl')
                    .tmpl({
                        'name': tmp['name'],
                        'id': tmp['id'],
                        'type': tmp['type'],
                        'size': tmp['size'],
                        'dir': tmp['dir_id'],
                        'extension': tmp['extension'],
                        'path': getPathIDs(tmp['path']),
                        'bitrate': isDef(tmp['metadata']) ? tmp['metadata']['bitrate'] : '0',
                        'duration': isDef(tmp['metadata']) ? tmp['metadata']['duration'] : '0',
                        'artist': isDef(tmp['metadata']) ? tmp['metadata']['artist'] : '',
                        'title': isDef(tmp['metadata']) ? tmp['metadata']['title'] : '',
                        'album': isDef(tmp['metadata']) ? tmp['metadata']['album'] : '',
                        'tracknumber': isDef(tmp['metadata']) ? tmp['metadata']['tracknumber'] : '',
                        'date': isDef(tmp['metadata']) ? tmp['metadata']['date'] : '',
                        'albumartist': isDef(tmp['metadata']) ? tmp['metadata']['albumartist'] : '',
                    })
                    .appendTo($('.file-item#file' + tmp['id']));
        } else {
            $('#fileListTmpl').tmpl(tmp).appendTo("div.hfs-filelist");
            $('#fileItemInputTmpl')
                    .tmpl({
                        'name': tmp['name'],
                        'id': tmp['id'],
                        'type': tmp['type'],
                        'size': tmp['size'],
                        'dir': tmp['dir_id'],
                        'extension': tmp['extension'],
                        'path': getPathIDs(tmp['path']),
                    })
                    .appendTo($('.file-item#file' + tmp['id']));
        }

        if (showpath == true && tmp['path'] !== undefined) {
            var filePathBuild = [];
            for (i in tmp['path']) {
                filePathBuild.push('<a href="#go=' + tmp['path'][i][0]['id'] + '">' + tmp['path'][i][0]['name'] + '</a>');
            }
            $('span.file-path#fp-' + tmp['id']).html(filePathBuild.join(' > '));
        }

        homefs_variables.files.push(tmp);
    }

}

function getPathIDs(path) {
    var id_mas = [];
    for (i in path) {
        id_mas.push(path[i][0]['id']);
    }
    return id_mas;
}

function draw_dirs_on_page(dirs, showpath) {
    for (var i in dirs) {
        var tmp = dirs[i];
        tmp['relative'] = date_delta_format(tmp['modified']);
        tmp['humanized'] = file_size_humanize(tmp['size']);

        $('#dirListTmpl').tmpl(tmp).appendTo("div.hfs-filelist");

        if (showpath == true && tmp['path'] !== undefined) {
            var filePathBuild = [];
            for (i in tmp['path']) {
                filePathBuild.push('<a href="#go=' + tmp['path'][i][0]['id'] + '">' + tmp['path'][i][0]['name'] + '</a>');
            }
            $('span.dir-path#fp-' + tmp['id']).html(filePathBuild.join(' > '));
        }
        homefs_variables.dirs.push(tmp);
    }
}

function next_page_label(action) {
    var array = {action: action};
    $("div.hfs-smartloader").html($('#nextFilesTmpl').tmpl(array));
}

function hfsStats() {
    var stCount = homefs_variables.files.length;
    var stSize = 0;
    for (i in homefs_variables.files) {
        stSize += parseInt(homefs_variables.files[i]['size']);
    }
    $("div.hfs-filestats").html("Total files size: <b>" + file_size_humanize(stSize) + "</b>").css({display: 'block'});
}

function getPageAudioList() {
    var audio = [];
    var index = 0;
    $("input.file-item[group='audio']").each(function() {
        var input = $(this);
        audio.push({
            'id': parseInt(input.attr('id').split('-')[1]),
            'name': input.attr('filename'),
            'dir': parseInt(input.attr('dir')),
            'extension': input.attr('extension'),
            'path': input.attr('path'),
            'duration': parseFloat(input.attr('duration')),
            'artist': input.attr('artist'),
            'title': input.attr('title'),
            'album': input.attr('album'),
            'size': parseInt(input.attr('size')),
            'bitrate': parseInt(input.attr('bitrate')),
            'scrolly': parseInt(input.attr('scrolly'))
        });
    });
    return audio;
}


function snapshotPlayingPage() {
    playingPage = {
        'nav': $(".hfs-location").html(),
        'data': $(".hfs-filelist-subwrap").html(),
        'title': document.title
    };
}

function restorePlayingPage() {
    if (playingPage) {
        $(".hfs-location").html(playingPage['nav']);
        $(".hfs-filelist-subwrap").html(playingPage['data']);
        document.title = playingPage['title'];

        reindex_current_page();
        index_links();
        animate_scroll();
    } else {
        window.location.hash = "";
    }
}
