function change() {
	var pic = document.getElementById("preview");
	var file = document.getElementById("f");
	var ext = file.value.substring(file.value.lastIndexOf(".") + 1)
			.toLowerCase();
	// gif在IE浏览器暂时无法显示
	if (ext != 'png' && ext != 'jpg' && ext != 'jpeg') {
		alert("文件必须为图片！");
		return;
	}
	// IE浏览器
	if (document.all) {

		file.select();
		var reallocalpath = document.selection.createRange().text;
		var ie6 = /msie 6/i.test(navigator.userAgent);
		// IE6浏览器设置img的src为本地路径可以直接显示图片
		if (ie6)
			pic.src = reallocalpath;
		else {
			// 非IE6版本的IE由于安全问题直接设置img的src无法显示本地图片，但是可以通过滤镜来实现
			pic.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod='image',src=\""
					+ reallocalpath + "\")";
			// 设置img的src为base64编码的透明图片 取消显示浏览器默认图片
			pic.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
		}
	} else {
		html5Reader(file);
	}
}

function html5Reader(file) {
	var file = file.files[0];
	var reader = new FileReader();
	reader.readAsDataURL(file);
	reader.onload = function(e) {
		var pic = document.getElementById("preview");
		pic.src = this.result;
	}
}





jQuery(function($) {
   /*
	 * $('.nav-list a').on('click', function(){
	 * 
	 * });
	 */
    $(".bootbox-html").on(ace.click_event, function() {
    	var self = $(this),
		dtitle = self.attr('data-title'),
		did = self.attr('data-id'),
		duri = self.attr('data-uri'),
		dwidth = parseInt(self.attr('data-width')),
		dheight = parseInt(self.attr('data-height')),
		dpadding = (self.attr('data-padding') != undefined) ? self.attr('data-padding') : '',
		dcallback = self.attr('data-callback');
		
		$.getJSON(duri, function(result){
			if(result.status == 1){
				//console.log(result.data);
				data = result.data;
				bootbox.dialog({
		    	      title: dtitle,
		    	      message: data,
		    	      buttons: {
		    	          success: {
		    	            label: "保存",
		    	            className: "btn-success",
		    	            callback: function () {
		    	            	console.log(this.info_form);
		    	            	//this.info_form.submit();
		    	            	var info_form =this.info_form;// this.dom.content.find('#info_form');
		    					if(info_form[0] != undefined){
		    						info_form.submit();
		    						if(dcallback != undefined){
		    							eval(dcallback+'()');
		    						}
		    						return false;
		    					}
		    					if(dcallback != undefined){
		    						eval(dcallback+'()');
		    					}
		    	            }
		    	          },
		    	          danger: {
		    	              label: "取消",
		    	              className: "btn-danger",
		    	              callback: function() {
		    	                //Example.show("uh oh, look out!");
		    	              }
		    	            }
		    	        }
		    	    });
				//$.dialog.get(did).content(result.data);
			} else {
				$('#altercon').removeClass("hidden");
				$('#warntext').html(result.msg);
				
				setTimeout(
						function(){
							$('#altercon').addClass("hidden");
						},
						500);
				
				//window.location.reload();
			}
		});
		
    
	});
  
    
    $(".bootbox-confirm").on(ace.click_event, function() {
    	var self = $(this),
		uri = self.attr('data-uri'),
		acttype = self.attr('data-acttype'),
		title = (self.attr('data-title') != undefined) ? self.attr('data-title') : lang.confirm_title,
		msg = self.attr('data-msg'),
		callback = self.attr('data-callback');
    	
		bootbox.setDefaults({
			  /**
			   * @optional String
			   * @default: en
			   * which locale settings to use to translate the three
			   * standard button labels: OK, CONFIRM, CANCEL
			   */
			  locale: "zh_CN"
			});
		
		
		//bootbox.setLocale("zh_CN");
		bootbox.confirm(msg, function(result) {
			if(result) {
				if(acttype == 'ajax'){
					$.getJSON(uri, function(result){
						if(result.status == 1){
							//$.pinphp.tip({content:result.msg});
							if(callback != undefined){
								eval(callback+'(self)');
							}else{
								window.location.reload();
							}
						}else{
							alert();
							//$.pinphp.tip({content:result.msg, icon:'error'});
						}
					});
				}else{
					location.href = uri;
				}
			}
		});
	});
    
    $("#bootbox-regular").on(ace.click_event, function() {
    	alert('dd');
		bootbox.prompt("What is your name?", function(result) {
			if (result === null) {
				//Example.show("Prompt dismissed");
			} else {
				//Example.show("Hi <b>"+result+"</b>");
			}
		});
	});
    
 
});


;(function($){
    //联动菜单
    $.fn.cate_select = function(options) {
        var settings = {
            field: 'J_cate_id',
            top_option: lang.please_select
        };
        if(options) {
            $.extend(settings, options);
        }

        var self = $(this),
            pid = self.attr('data-pid'),
            uri = self.attr('data-uri'),
            selected = self.attr('data-selected'),
            selected_arr = [];
        if(selected != undefined && selected != '0'){
        	if(selected.indexOf('|')){
        		selected_arr = selected.split('|');
        	}else{
        		selected_arr = [selected];
        	}
        }
        self.nextAll('.J_cate_select').remove();
        $('<option value="">--'+settings.top_option+'--</option>').appendTo(self);
        $.getJSON(uri, {id:pid}, function(result){
            if(result.status == '1'){
                for(var i=0; i<result.data.length; i++){
                $('<option value="'+result.data[i].id+'">'+result.data[i].name+'</option>').appendTo(self);
                }
            }
            if(selected_arr.length > 0){
            	//IE6 BUG
            	setTimeout(function(){
            		self.find('option[value="'+selected_arr[0]+'"]').attr("selected", true);
	        		self.trigger('change');
            	}, 1);
            }
        });

        var j = 1;
        $('.J_cate_select').off('change').on('change', function(){
            var _this = $(this),
            _pid = _this.val();
            _this.nextAll('.J_cate_select').remove();
            if(_pid != ''){
                $.getJSON(uri, {id:_pid}, function(result){
                    if(result.status == '1'){
                        var _childs = $('<select class="J_cate_select mr10" data-pid="'+_pid+'"><option value="">--'+settings.top_option+'--</option></select>')
                        for(var i=0; i<result.data.length; i++){
                            $('<option value="'+result.data[i].id+'">'+result.data[i].name+'</option>').appendTo(_childs);
                        }
                        _childs.insertAfter(_this);
                        if(selected_arr[j] != undefined){
                        	//IE6 BUG
                        	//setTimeout(function(){
			            		_childs.find('option[value="'+selected_arr[j]+'"]').attr("selected", true);
				        		_childs.trigger('change');
			            	//}, 1);
			            }
                        j++;
                    }
                });
                $('#'+settings.field).val(_pid);
            }else{
            	$('#'+settings.field).val(_this.attr('data-pid'));
            }
        });
    }
})(jQuery);



//显示大图
;(function($){
	$.fn.preview = function(){
		var w = $(window).width();
		var h = $(window).height();
		
		$(this).each(function(){
			$(this).hover(function(e){
				if(/.png$|.gif$|.jpg$|.bmp$|.jpeg$/.test($(this).attr("data-bimg"))){
					$("body").append("<div id='preview'><img src='"+$(this).attr('data-bimg')+"' /></div>");
				}
				var show_x = $(this).offset().left + $(this).width();
				var show_y = $(this).offset().top;
				var scroll_y = $(window).scrollTop();
				$("#preview").css({
					position:"absolute",
					padding:"4px",
					border:"1px solid #f3f3f3",
					backgroundColor:"#eeeeee",
					top:show_y + "px",
					left:show_x + "px",
					zIndex:1000
				});
				$("#preview > div").css({
					padding:"5px",
					backgroundColor:"white",
					border:"1px solid #cccccc"
				});
				if (show_y + 230 > h + scroll_y) {
					$("#preview").css("bottom", h - show_y - $(this).height() + "px").css("top", "auto");
				} else {
					$("#preview").css("top", show_y + "px").css("bottom", "auto");
				}
				$("#preview").fadeIn("fast")
			},function(){
				$("#preview").remove();
			})					  
		});
	};
})(jQuery);