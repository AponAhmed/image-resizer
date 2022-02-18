(function ($) {
    $(document).ready(function () {
        $("#newSize").click(function () {
            let lastItem = $(".size-item").last();
            let nxtIndex = Number(lastItem.attr('data-index')) + 1;
            nxtIndex = nxtIndex ? nxtIndex : 1;
            let elm = '<div class="size-item" data-index="' + nxtIndex + '">\n\
                    <input type="text" class="sizeName" name="media-size[' + nxtIndex + '][name]" placeholder="Name">\n\
                    <input type="text" class="sizeinput" name="media-size[' + nxtIndex + '][width]" placeholder="Width">\n\
                    <input type="text" class="sizeinput" name="media-size[' + nxtIndex + '][height]" placeholder="Width"><span class="reSizeItem" onclick="reSizeItem(this)">&times;</span>\n\
                </div>';
            $(".newSize").before(elm);
        });
    });

})(jQuery)

function reSizeItem(_this) {
    jQuery(_this).closest('.size-item').remove();
}

//ajax_object.ajax_url
function updateMediaSizeOption(_this) {
    let btn = jQuery(_this);
    btn.find(".dashicons").remove();
    btn.prepend('<span class="dashicons dashicons-update loading"></span>');
    var data = {action: "media_resize_option", data: jQuery(_this).closest('form').serialize()};
    jQuery.post(ajax_object.ajax_url, data, function (response) {
        btn.find('.loading').removeClass('loading dashicons-update').addClass('dashicons-saved');
        //console.log(response);
    })
}
var inProcess = false;
var processStart = false;
setInterval(function () {
    if (processStart) {
        if (inProcess === false) {
            buildSize();
        }
    }
    //console.log(processStart, inProcess)
}, 1000);


function buildSize() {
    (function ($) {
        inProcess = true;
        $.post(ajax_object.ajax_url, {action: "buildImageSize"}, function (response) {
            var obj = JSON.parse(response);

            var prc = (obj.done * 100) / obj.total;
            //console.log(prc.toFixed(2));
            $(".buildProgress").css('width', prc.toFixed(2) + "%").attr('aria-valuenow', prc.toFixed(2));

            if (Number(obj.total) >= Number(obj.done)) {
                console.log('ReQue');
                inProcess = false; //Re Enable Que 
            }
            if (obj.info == "complete") {
                inProcess = false;
                processStart = false;
                $(".ImageBuildTriger").attr('data-state', 'false');
                $(".ImageBuildTriger").html('Start').removeAttr('style');
                $(".buildProgress").removeClass("progress-bar-animated");
                $(".ImageBuildTriger").after("<span class='completeResp'>&nbsp;&nbsp;Process Complete.<a href='javascript:void(0)' onclick='clearHistoryImgBuild()'>Clear History</a></span>");
            }
        })
    })(jQuery)
}

function ImageBuildTriger(_this) {
    (function ($) {
        let currState = $(_this).attr('data-state');
        if (currState === "false") {
            processStart = true;
            $(_this).attr('data-state', 'true');
            $(_this).html('Stop').css('background', '#d25151');
            $(".buildProgress").addClass("progress-bar-animated");
        } else {
            processStart = false;
            $(_this).attr('data-state', 'false');
            $(_this).html('Start').removeAttr('style');
            $(".buildProgress").removeClass("progress-bar-animated");
        }
    })(jQuery)
}
function clearHistoryImgBuild() {
    (function ($) {
        $.post(ajax_object.ajax_url, {action: "clean_size_build_history"}, function (response) {
            $(".completeResp").remove();
            $(".buildProgress").css('width', "0%").attr('aria-valuenow', "0");
        });
    })(jQuery)
}