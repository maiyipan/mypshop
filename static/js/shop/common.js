$.fn.TabPanel = function(options){
	var defaults = {    
		tab: 0      
	}; 
	var opts = $.extend(defaults, options);
	var t = this;
	$(t).find('.shop-tab-nav li').click(function(){
		$(this).addClass("on").siblings().removeClass();
		var index = $(this).index();
		$(t).find('.shop-tab-content .shop-tab-item').eq(index).show().siblings('.shop-tab-item').hide();
		if(opts.callback)opts.callback(index);
	});
	$(t).find('.shop-tab-nav li').eq(opts.tab).click();
}

var SHOP = {};
SHOP.isChinese = function(obj,isReplace){
 	var pattern = /[\u4E00-\u9FA5]|[\uFE30-\uFFA0]/i
 	if(pattern.test(obj.value)){
 		if(isReplace)obj.value=obj.value.replace(/[\u4E00-\u9FA5]|[\uFE30-\uFFA0]/ig,"");
 		return true;
 	}
 	return false;
 }
SHOP.isFloat = function(obj){
 	var pattern = /^([1-9][\d]{0,7}|0)(\.[\d]{1,2})?$/
 	return pattern.test(obj);
 }

SHOP.isNumberKey = function(evt){
 	var charCode = (evt.which) ? evt.which : event.keyCode;
 	if (charCode > 31 && (charCode < 48 || charCode > 57)){
 		return false;
 	}else{		
 		return true;
 	}
 } 
SHOP.isNumberdoteKey = function(evt){
 	var e = evt || window.event; 
 	var srcElement = e.srcElement || e.target;
 	
 	var charCode = (evt.which) ? evt.which : event.keyCode;			
 	if (charCode > 31 && ((charCode < 48 || charCode > 57) && charCode!=46)){
 		return false;
 	}else{
 		if(charCode==46){
 			var s = srcElement.value;			
 			if(s.length==0 || s.indexOf(".")!=-1){
 				return false;
 			}			
 		}		
 		return true;
 	}
 }
SHOP.msg = function(msg, options, func){
	var opts = {};
	if(typeof(options)!='function'){
		opts = $.extend(opts,{time:2000,shade: [0.4, '#000000']},options);
		return layer.msg(msg, opts, func);
	}else{
		return layer.msg(msg, options);
	}
}

SHOP.validTime = function(startTime,endTime){
    var arr1 = startTime.split("-");
    var arr2 = endTime.split("-");
    var date1=new Date(parseInt(arr1[0]),parseInt(arr1[1])-1,parseInt(arr1[2]),0,0,0); 
    var date2=new Date(parseInt(arr2[0]),parseInt(arr2[1])-1,parseInt(arr2[2]),0,0,0);
    if(date1.getTime()>date2.getTime()) {                                
    	return false;
    }else{
    	return true;
    }
    return false;
}

SHOP.validTime2 = function comptime(beginTime,endTime) {  
    var beginTimes = beginTime.substring(0, 10).split('-');  
    var endTimes = endTime.substring(0, 10).split('-');  
    beginTime = beginTimes[1] + '-' + beginTimes[2] + '-' + beginTimes[0] + ' ' + beginTime.substring(10, 19);  
    endTime = endTimes[1] + '-' + endTimes[2] + '-' + endTimes[0] + ' ' + endTime.substring(10, 19);  
    var a = (Date.parse(endTime) - Date.parse(beginTime)) / 3600 / 1000;  
    if (a < 0) {  
       return false;
    } else {  
        return true;
    }  
}  
