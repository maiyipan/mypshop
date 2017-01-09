function upload(options){
	$(options.dom).upload({
		el:options.dom,
        action: options.action, 
        fileName: "Filedata",   
        afterName: options.afterName, 
        dir: options.dir, 
        params: {suffix: '*.jpg;*.gif;*.png;*.jpeg'}, 
        accept: ".jpg,.png,.gif,.jpeg",    
        dataType :"json",
        complete: function (d) { 
			 if(d.error == 0){
				 $("#imgPreview").parent().show();
				 $("#imgPreview").attr("src",ROOT_PATH+d.path);
				 $("."+options.afterName).val(d.name);
			 }else{
			    SHOP.msg(d.msg, {icon: 5});
			 }
        }
    });
}
/**
 *  author: leunpha
 *  date: 2014-5-1
 *  version 2.0
 *  dependency: jquery.js
 */
$.fn.upload = function (options) {
    options = options || {};
    options.dom = this;
    $.upload(options);
}

$.upload = function (options) {
    var settings = {
        dom: this,
        action: "",
        fileName: "file",
        params: {},
        accept: ".jpg,.png",
        ieSubmitBtnText: "上传",
		dataType:"text",
        complete: function (result) {
        },
        submit: function () {

        }
    }
    settings = $.extend(settings, options);
    var ele = $(settings.dom);
 
    var iframeName = "leunpha_iframe_v" + Math.random() * 100;
    var width = ele.outerWidth();
    var height = ele.outerHeight(); 
    var iframe = $("<iframe name='"+iframeName+"' style='display:none;' id='"+iframeName+"'></iframe>");
    var form = $("<form></form>");
    form.attr({
        target: iframeName,
        action: settings.action,
        method: "post",
        "class": "ajax_form",
        enctype: "multipart/form-data"
    }).css({
            width: width,
            height: height,
            position: "absolute",
            top: (ele.offset().top),
            left: (ele.offset().left)
        });
    var input = $("<input type='file'/>");
    input.attr({
        accept: settings.accept,
        name: settings.fileName
    })
        .css({
            opacity: 0,
            position: "absolute",
            width: width,
            height: height + "px",
            cursor: "pointer"
        });
    input.change(function () {
        settings.submit.call(form);
        $(this).parent("form").submit();
    });
    form.append(input);
    form.append("<input type='hidden' name='dir' value='"+settings.dir+"'/>");
    $("body").append(iframe);
    iframe.after(form);
    for (var param in settings.params) {
        var div = $("<input type='hidden'/>").attr({name: param, value: settings.params[param]});
        input.after(div)
        div = null;
        delete div;
    }
    iframe.load(function () {
        var im = document.getElementById(iframeName);
        var text = $(im.contentWindow.document.body).text();
		if(text){
			var dataType = settings.dataType.toLocaleUpperCase();
			if( dataType == "JSON"){
				try{
					if(typeof text=="string")
						text = $.parseJSON(text);
				}catch(e){
					text = "error";
				}
			}else if(dataType == "xml"){
			}else{
				//默认就是text
			}
			settings.complete.call(null, text);
		}
    });
}