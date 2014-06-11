function initEquipment(_object_id) {
    jeedom.object.all(function(objects) {
        var li = ' <ul data-role="listview">';
        for (var i in objects) {
             if (objects[i].isVisible == 1) {
                var icon = '';
                if (isset(objects[i].display) && isset(objects[i].display.icon)) {
                    icon = objects[i].display.icon;
                }
                li += '<li></span><a href="#" class="link" data-page="equipment" data-title="' + icon.replace(/\"/g, "\'") + ' ' + objects[i].name + '" data-option="' + objects[i].id + '"><span>' + icon + '</span> ' + objects[i].name + '</a></li>';
            }
        }
        li += '</ul>';
        panel(li);
    });

    if (isset(_object_id) && is_numeric(_object_id)) {
        jeedom.object.toHtml(_object_id, 'mobile', !jeedom.workflow.object[_object_id], true, function(html) {
            $('#div_displayEquipement').empty().html(html).trigger('create');
            setTileSize('.eqLogic');
            $('#div_displayEquipement').masonry();
        });
    } else {
        $('#panel_right').panel('open');
    }

    $(window).on("orientationchange", function(event) {
        setTileSize('.eqLogic');
        $('#div_displayEquipement').masonry();
    });
}