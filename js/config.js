$( function() {
    var xmpProgressBar = $("#xmp-progress-bar");
    var xmpProgressLabel = $(".progress-label");

    var xmpRefreshButton = $('#xmp-refresh-metadata');
    var xmpCancelButton = $('#xmp-cancel-refresh');

    var statusTimer = null;

    var xmpFieldsApplyButton = $('#xmp-fields-apply');
    var xmpFieldsNotificationBar = $('#xmp-fields-notification-bar');
    var xmpFieldsNotificationTimer = null;
    var xmpFieldsNotificationCounter = 0;

    var xmpFieldDeleteButtons = $('.xmp-field-delete');

    xmpProgressBar.progressbar({
        value: false,
        create: function() {
            xmpProgressLabel.text('Refreshing...');
        },
        change: function() {
            if (xmpProgressBar.progressbar('value') != false) {
                xmpProgressLabel.text(xmpProgressBar.progressbar('value') + '%');
            }
        },
        complete: function() {
            xmpProgressLabel.text('Complete!');
        }
    });

    xmpRefreshButton.on('click', function() {
        $.ajax({
            url: 'index.php',
            data: {
                file:      'extensible_metadata/refresh',
                action:    'start',
                overwrite: $('#plugin_extensible_metadata_overwrite')[0].checked
            },
            cache: false,
            dataType: 'json',
            beforeSend: function() {
                startRefresh();
            }
        });
    });

    xmpCancelButton.on('click', function() {
        $.ajax({
            url: 'index.php',
            data: {
                file:   'extensible_metadata/refresh',
                action: 'cancel'
            },
            cache: false,
            dataType: 'json',
            success: function(data) {
                if (data.status == 'success') {
                    finishRefresh();
                }
            }
        });
    });

    xmpFieldsApplyButton.on('click', function() {
        $.ajax({
            url: 'index.php?file=extensible_metadata/fields&action=save',
            type: 'POST',
            data: {
                display_name: $('.xmp-field-display-name').serialize(),
                displayed:    $('.xmp-field-displayed:checked').serialize(),
                indexed:      $('.xmp-field-indexed:checked').serialize()
            },
            cache: false,
            dataType: 'json',
            success: function(data) {
                if (data.status == 'success') {
                    xmpFieldsSaved();
                }
            }
        });
    });

    function deleteXmpField(id) {
        $.ajax({
            url: 'index.php?file=extensible_metadata/fields&action=delete',
            type: 'POST',
            data: {
                id: id,
            },
            cache: false,
            dataType: 'json',
            success: function(data) {
                if (data.status == 'success') {
                    var deletedRow = $('#xmp-field-row\\[' + id + '\\]');
                    var nextRow = deletedRow.next();
                    deletedRow.remove();
                    while (nextRow.length != 0) {
                        nextRow.find('td').toggleClass('tableb_alternate');
                        nextRow = nextRow.next();
                    }
                }
            }
        })
    }

    xmpFieldDeleteButtons.each(function() {
        $(this).click(function() {
            deleteXmpField($(this).find('input').val());
        });
    });

    function xmpFieldsSaved() {
        var fadeTime = 500;
        var startFadeOut = 2500;
        var endTime = startFadeOut + fadeTime;
        var interval = 50;
        var maxOpacity = 0.8;
        var startR = 255;
        var startG = 255;
        var startB = 255;
        var targetR = 138;
        var targetG = 226;
        var targetB = 52;

        if (xmpFieldsNotificationTimer == null) {
            xmpFieldsNotificationCounter = 0;
            xmpFieldsNotificationTimer = setInterval(xmpFieldsSaved, interval);
        } else {
            var time = interval * xmpFieldsNotificationCounter;
            if (time <= fadeTime) {
                var fade = (time / fadeTime);
                var opacity = fade * maxOpacity;
                var r = Math.round(startR - (startR - targetR) * fade);
                var g = Math.round(startG - (startG - targetG) * fade);
                var b = Math.round(startB - (startB - targetB) * fade);
                xmpFieldsNotificationBar.css('display', 'block');
                xmpFieldsNotificationBar.css('opacity', opacity);
                xmpFieldsNotificationBar.css('background-color', 'rgb(' + r + ', ' + g + ', ' + b + ')');
            } else if (time >= startFadeOut && time < endTime) {
                time -= startFadeOut;
                var fade = 1 - (time / fadeTime);
                var opacity = fade * maxOpacity;
                var r = Math.round(startR - (startR - targetR) * fade);
                var g = Math.round(startG - (startG - targetG) * fade);
                var b = Math.round(startB - (startB - targetB) * fade);
                xmpFieldsNotificationBar.css('display', 'block');
                xmpFieldsNotificationBar.css('opacity', opacity);
                xmpFieldsNotificationBar.css('background-color', 'rgb(' + r + ', ' + g + ', ' + b + ')');
            } else if (time >= endTime) {
                clearInterval(xmpFieldsNotificationTimer);
                xmpFieldsNotificationTimer = null;
                xmpFieldsNotificationBar.css('display', 'none');
                xmpFieldsNotificationBar.css('opacity', '0');
                xmpFieldsNotificationBar.css('background-color', 'rgb(' + startR + ', ' + startG + ', ' + startB + ')');
            }
        }

        xmpFieldsNotificationCounter++;
    }

    function startRefresh() {
        xmpRefreshButton.attr('disabled', true);
        xmpCancelButton.attr('disabled', false);

        xmpProgressLabel.text('Refreshing...');
        xmpProgressBar.progressbar('value', false);
        xmpProgressBar.attr('hidden', false);

        startTimer();
    }

    function finishRefresh() {
        stopTimer();
        xmpRefreshButton.attr('disabled', false);
        xmpCancelButton.attr('disabled', true);
        xmpProgressBar.attr('hidden', true);
    }

    function startTimer() {
        if (statusTimer == null) {
            statusTimer = setInterval(statusTick, 5000);
        }
    }

    function stopTimer() {
        if (statusTimer != null) {
            clearInterval(statusTimer);
            statusTimer = null;
        }
    }

    function statusTick() {
        $.ajax({
            url: 'index.php',
            data: {
                file:   'extensible_metadata/refresh',
                action: 'status'
            },
            cache: false,
            dataType: 'json',
            success: function(data) {
                if (data.status == 'success') {
                    if (data.last_refresh) {
                        $('#xmp-last-refresh').text(data.last_refresh); // TODO: format time
                    }
                    if (data.images_processed && data.total_images) {
                        $('#xmp-images-processed').text(data.images_processed + '/' + data.total_images);
                        if (data.total_images > 0) {
                            var percent_complete = (data.images_processed / data.total_images * 100);
                            xmpProgressBar.progressbar('value', percent_complete);
                        }
                    }
                    if (data.xmp_files_created) {
                        $('#xmp-sidecar-files-created').text(data.xmp_files_created);
                    }
                    if (data.xmp_files_skipped) {
                        $('#xmp-sidecar-files-skipped').text(data.xmp_files_skipped);
                    }
                    if (data.refresh_status == 'complete') {
                        finishRefresh();
                    }
                }
            }
        });
    }
});
