$( function() {
    var xmpProgressBar = $("#xmp-progress-bar");
    var xmpProgressLabel = $(".progress-label");

    var xmpRefreshButton = $('#xmp-refresh-metadata');
    var xmpCancelButton = $('#xmp-cancel-refresh');

    var statusTimer = null;

    xmpProgressBar.progressbar({
        value: false,
        create: function() {
            xmpProgressLabel.text('Refreshing...');
        },
        change: function() {
            xmpProgressLabel.text(xmpProgressBar.progressbar('value') + '%');
        },
        complete: function() {
            xmpProgressLabel.text('Complete!');
        }
    });

    xmpRefreshButton.on('click', function() {
        $.ajax({
            url: 'index.php?file=extensible_metadata/refresh&action=start',
            cache: false,
            dataType: 'json',
            beforeSend: function() {
                startRefresh();
            }
        });
    });

    xmpCancelButton.on('click', function() {
        $.ajax({
            url: 'index.php?file=extensible_metadata/refresh&action=cancel',
            cache: false,
            dataType: 'json',
            success: function(data) {
                if (data.status == 'success') {
                    finishRefresh();
                }
            }
        });
    });

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
            url: 'index.php?file=extensible_metadata/refresh&action=status',
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
