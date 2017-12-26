$( function() {
    var xmpProgressBar = $("#xmp-progress-bar");
    var xmpProgressLabel = $(".progress-label");

    var xmpRefreshButton = $('#xmp-refresh-metadata');
    var xmpCancelButton = $('#xmp-cancel-refresh');

    var xmpMetadataOverwriteCheckbox = $('#plugin_extensible_metadata_overwrite')[0];
    var xmpRefreshErrorDiv = $('#xmp-refresh-error');
    var xmpIndexDirtyDiv = $('#xmp-index-dirty');
    var xmpRefreshStatusDiv = $('#xmp-refresh-status');
    var xmpLastRefreshLabel = $('#xmp-last-refresh');
    var xmpImagesProcessedLabel = $('#xmp-images-processed');
    var xmpSidecarFilesCreatedLabel = $('#xmp-sidecar-files-created');
    var xmpSidecarFilesSkippedLabel = $('#xmp-sidecar-files-skipped');
    var xmpRefreshSpacerDiv = $('#xmp-refresh-spacer');

    var xmpFieldTable = $('.xmp-field-table');
    var xmpFieldsApplyRow = $('#xmp-fields-apply-row');
    var xmpFieldsApplyCol = $('#xmp-fields-apply-col');
    var xmpFieldsApplyButton = $('#xmp-fields-apply');
    var xmpFieldsNotificationTimer = null;
    var xmpFieldsNotificationCounter = 0;

    var xmpFieldDeleteButtons = $('.xmp-field-delete');

    var xmpRefreshInProgress = false;
    var xmpRefreshCancelRequested = false;

    var xmpDataImagesProcessed = 0;
    var xmpDataFilesCreated = 0;
    var xmpDataFilesSkipped = 0;

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
        if (!xmpRefreshInProgress) {
            processRefresh(0);
        }
    });

    function processRefresh(page) {
        $.ajax({
            url: 'index.php',
            data: {
                file:      'extensible_metadata/refresh',
                action:    'process',
                overwrite: xmpMetadataOverwriteCheckbox.checked,
                page:      page
            },
            cache: false,
            dataType: 'json',
            beforeSend: function() {
                if (page == 0) {
                    startRefresh();
                }
            },
            success: function(data) {
                if (data.status == 'success') {
                    if (data.last_refresh) {
                        xmpLastRefreshLabel.text(data.last_refresh);
                    }
                    if (data.images_processed) {
                        xmpDataImagesProcessed += data.images_processed;
                    }
                    if (data.total_images) {
                        xmpImagesProcessedLabel.text(xmpDataImagesProcessed + '/' + data.total_images);
                        if (data.total_images > 0) {
                            var percent_complete = (xmpDataImagesProcessed / data.total_images * 100);
                            xmpProgressBar.progressbar('value', percent_complete);
                        }
                    }
                    if (data.xmp_files_created) {
                        xmpDataFilesCreated += data.xmp_files_created;
                    }
                    if (data.xmp_files_skipped) {
                        xmpDataFilesSkipped += data.xmp_files_skipped;
                    }
                    xmpSidecarFilesCreatedLabel.text(xmpDataFilesCreated);
                    xmpSidecarFilesSkippedLabel.text(xmpDataFilesSkipped);
                    if (data.index_dirty == '0') {
                        xmpIndexDirtyDiv.attr('hidden', true);
                    }
                    $.each(data.new_fields, function(index, value) {
                        addXmpField(value.id, value.name);
                    });
                    if (data.more_images == true && !xmpRefreshCancelRequested) {
                        processRefresh(page + 1);
                    } else {
                        finishRefresh(false);
                    }
                } else {
                    finishRefresh(true);
                }
            },
            error: function() {
                finishRefresh(true);
            }
        });
    }

    function startRefresh() {
        xmpRefreshErrorDiv.attr('hidden', true);
        xmpRefreshStatusDiv.removeAttr('hidden');
        xmpRefreshSpacerDiv.removeAttr('hidden');

        xmpProgressLabel.text('Refreshing...');
        xmpProgressBar.progressbar('value', false);
        xmpProgressBar.attr('hidden', false);

        xmpImagesProcessedLabel.text('0');
        xmpSidecarFilesCreatedLabel.text('0');
        xmpSidecarFilesSkippedLabel.text('0');

        xmpRefreshInProgress = true;
        xmpRefreshCancelRequested = false;

        xmpDataImagesProcessed = 0;
        xmpDataFilesCreated = 0;
        xmpDataFilesSkipped = 0;
    }

    function finishRefresh(error) {
        xmpRefreshSpacerDiv.attr('hidden', true);
        xmpProgressBar.attr('hidden', true);

        xmpRefreshInProgress = false;

        if (error) {
            xmpRefreshErrorDiv.removeAttr('hidden');
        }
    }

    xmpCancelButton.on('click', function() {
        if (xmpRefreshInProgress) {
            xmpRefreshCancelRequested = true;
        }
    });

    xmpFieldTable.find('td:first').prepend(
        '<div class="notification-bar" id="xmp-fields-notification-bar">' +
        '<img src="images/icons/ok.png" width="16" height="16" style="vertical-align: bottom;">Saved' +
        '</div>');
    var xmpFieldsNotificationBar = xmpFieldTable.find('#xmp-fields-notification-bar');

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
                    if (data.index_dirty == true) {
                        xmpIndexDirtyDiv.removeAttr('hidden');
                    }
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

    function addXmpField(id, name) {
        var altTableClass = 'tableb_alternate';
        xmpFieldsApplyCol.toggleClass(altTableClass);
        if (xmpFieldsApplyCol.hasClass(altTableClass)) {
            altTableClass = '';
        }
        var row = '<tr id="xmp-field-row[' + id + ']">' +
            '<td class="tableb ' + altTableClass + '">' +
            name +
            '</td>' +
            '<td class="tableb ' + altTableClass + '">' +
            '<div style="width: 100%; display: table;">' +
            '<input type="text" class="xmp-field-display-name" name="xmp-field-display-name[' + id + ']" value="" style="display: table-cell; width: 100%;" />' +
            '</div>' +
            '</td>' +
            '<td class="tableb ' + altTableClass + '" style="text-align: center;">' +
            '<input type="checkbox" class="xmp-field-displayed" name="xmp-field-displayed[' + id + ']" value="' + id + '" />' +
            '</td>' +
            '<td class="tableb ' + altTableClass + '" style="text-align: center;">' +
            '<input type="checkbox" class="xmp-field-indexed" name="xmp-field-indexed[' + id + ']" value="' + id + '" />' +
            '</td>' +
            '<td class="tableb ' + altTableClass + '" style="text-align: center;">' +
            '<a class="xmp-field-delete" id="xmp-field-delete[' + id + ']" href="javascript:void(0);">' +
            '<input type="hidden" value="' + id + '" />' +
            '<img src="images/icons/delete.png" border="0" alt="" width="16" height="16" class="icon" />' +
            '</a>' +
            '</td>' +
            '</tr>';
        xmpFieldsApplyRow.before(row);
        $('#xmp-field-delete\\[' + id + '\\]').click(function() {
            deleteXmpField(id);
        });
    }

    function xmpFieldsSaved() {
        var fadeTime = 500;
        var startFadeOut = 2500;
        var endTime = startFadeOut + fadeTime;
        var interval = 50;
        var maxOpacity = 0.8;
        var startR = 255;
        var startG = 255;
        var startB = 255;
        var targetR = 0;
        var targetG = 128;
        var targetB = 0;

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
});
